<?php

namespace App\Support;

use App\Helpers\Helper;
use App\Jobs\SendCompanyOfferDecisionEmail;
use App\Mail\CompanyOfferDecisionMail;
use App\Models\CompanyQuotation;
use App\Models\OfferPriceChange;
use App\Models\Project;
use App\Models\ProjectCancellation;
use App\Models\ProjectUpdate;
use App\Models\Quote;
use App\Models\SolarCompany;

/**
 * Ending a deal the customer already accepted, without the customer losing their request:
 *
 *  - the company withdraws before work starts          → ProjectRepository::withdraw (withdrawn)
 *  - after work started, or the company is flagged     → a request; Admin approves → approved
 *  - the customer declines the company's price change  → CustomerRepository::declinePriceChange
 *
 * Every path ends in endDeal(): the project is cancelled (soft-deleted, history kept in
 * tbl_project_cancellations), the company's offer is withdrawn, the request goes back to
 * "pending" and open to all companies, and the other companies' offers can be chosen again.
 */
final class OfferWithdrawal
{
    /**
     * Call inside a transaction with the project locked. Saves $cancellation (with the number of
     * reopened offers) and returns the ids of the offers put back for the customer to choose from.
     *
     * @return int[]
     */
    public static function endDeal(Project $project, ProjectCancellation $cancellation, string $actorType, ?int $actorId): array
    {
        $quote = Quote::query()->whereKey($project->quote_id)->lockForUpdate()->first();
        $offer = $project->company_quotation_id
            ? CompanyQuotation::query()->whereKey($project->company_quotation_id)->lockForUpdate()->first()
            : null;

        // A price change still waiting has nothing left to apply to.
        OfferPriceChange::query()
            ->where('project_id', $project->id)
            ->where('status', OfferPriceChange::PENDING)
            ->update(['status' => OfferPriceChange::CANCELLED, 'responded_at' => now()]);

        // Any other open request on this project is answered by this one.
        ProjectCancellation::query()
            ->where('project_id', $project->id)
            ->where('status', ProjectCancellation::REQUESTED)
            ->whereKeyNot($cancellation->getKey() ?? 0)
            ->update(['status' => ProjectCancellation::CANCELLED, 'decided_at' => now()]);

        ProjectUpdate::query()->create([
            'project_id' => $project->id,
            'updated_by_type' => $actorType,
            'updated_by_id' => $actorId,
            'old_status' => $project->status,
            'new_status' => Project::CANCELLED,
            'progress_percent' => (int) $project->progress_percent,
            'message' => 'The deal ended: ' . $cancellation->reasonText('en')
                . ($cancellation->note ? ' — ' . $cancellation->note : '')
                . '. The request was reopened for other offers.',
            'visible_to_customer' => false,
        ]);

        // Soft-deleted so every "project of this request" lookup ignores it from now on.
        $project->status = Project::CANCELLED;
        $project->save();
        $project->delete();

        if ($offer) {
            $offer->status = 'withdrawn';
            $offer->save();
        }

        $reopenedIds = [];

        if ($quote) {
            $reopenedIds = CompanyQuotation::query()
                ->where('quote_id', $quote->id)
                ->when($offer, fn($query) => $query->whereKeyNot($offer->id))
                ->where('status', 'not_selected')
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();

            if ($reopenedIds) {
                // Without touching updated_at: an offer's validity is counted from it,
                // so a reopened offer keeps its real "valid until" date.
                CompanyQuotation::query()->whereKey($reopenedIds)->toBase()->update(['status' => 'submitted']);
            }

            // Open to every company again, including a request that came from a package offer.
            $quote->status = 'pending';
            $quote->company_id = null;
            $quote->save();
        }

        $cancellation->reopened_offers = count($reopenedIds);
        $cancellation->save();

        return $reopenedIds;
    }

    /**
     * After the transaction: push the customer that their request is open again, email the
     * companies whose offers were put back, and optionally the company that left.
     *
     * @param int[] $reopenedOfferIds
     */
    public static function notifyDealEnded(
        ProjectCancellation $cancellation,
        array $reopenedOfferIds,
        bool $notifyCustomer = true,
        ?string $companyDecision = null,
        array $companyExtra = []
    ): void {
        if ($notifyCustomer && $cancellation->customer_id) {
            $companyName = (string) (SolarCompany::query()->whereKey($cancellation->company_id)->value('name') ?: 'The company');
            $reference = self::reference((int) $cancellation->quote_id);

            app(Helper::class)->notifyCustomer(
                (int) $cancellation->customer_id,
                'offer_withdrawn',
                [
                    'en' => [':company' => $companyName, ':ref' => $reference, ':reason' => lcfirst($cancellation->reasonText('en'))],
                    'ar' => [':company' => $companyName, ':ref' => $reference, ':reason' => $cancellation->reasonText('ar')],
                ],
                'company',
                (int) $cancellation->quote_id
            );
        }

        foreach ($reopenedOfferIds as $offerId) {
            SendCompanyOfferDecisionEmail::dispatch($offerId, CompanyOfferDecisionMail::REOPENED);
        }

        if ($companyDecision && $cancellation->company_quotation_id) {
            SendCompanyOfferDecisionEmail::dispatch((int) $cancellation->company_quotation_id, $companyDecision, $companyExtra + [
                'reasonText' => $cancellation->reasonText('en'),
                'reasonTextAr' => $cancellation->reasonText('ar'),
            ]);
        }

        ActivityLogger::log(
            'deal_ended',
            'Deal ended on request ' . self::reference((int) $cancellation->quote_id) . ': ' . $cancellation->reasonText('en')
                . ' (' . count($reopenedOfferIds) . ' offers reopened)',
            $cancellation->project,
            [
                'quote_id' => (int) $cancellation->quote_id,
                'project_id' => (int) $cancellation->project_id,
                'company_id' => (int) $cancellation->company_id,
                'cancellation_id' => (int) $cancellation->id,
                'status' => $cancellation->status,
            ],
            null,
            'projects'
        );
    }

    /**
     * Applies an accepted price change to the offer: new price, commission worked out again on
     * the same terms, and the invoice balance when the offer has one. Call inside the transaction.
     */
    public static function applyPriceChange(CompanyQuotation $offer, OfferPriceChange $change): void
    {
        $offer->price = $change->new_price;
        // Same rate as when the customer accepted; a custom percent or fixed amount is kept.
        $offer->applyCommission((float) $offer->commission_rate);
        $offer->save();

        $invoice = $offer->invoice()->first();

        if ($invoice) {
            $invoice->balance_due = $change->new_price;
            $invoice->save();
        }
    }

    public static function reference(int $quoteId): string
    {
        return '#Q-' . str_pad((string) $quoteId, 5, '0', STR_PAD_LEFT);
    }
}
