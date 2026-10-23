<?php

namespace App\Jobs;

use App\Mail\CompanyOfferDecisionMail;
use App\Models\CompanyQuotation;
use App\Models\Project;
use App\Models\SolarCompany;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCompanyOfferDecisionEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $offerId,
        public string $decision,
    ) {
        // Only send once the accept/reject transaction has committed.
        $this->afterCommit();
    }

    public function handle(): void
    {
        $offer = CompanyQuotation::query()
            ->with(['quote.customer'])
            ->find($this->offerId);

        if (!$offer) {
            Log::warning('Offer decision email skipped: offer not found.', [
                'offer_id' => $this->offerId,
                'decision' => $this->decision,
            ]);

            return;
        }

        $company = SolarCompany::query()->find($offer->company_id);

        if (!$company || empty($company->email)) {
            Log::warning('Offer decision email skipped: company has no email.', [
                'offer_id' => $offer->id,
                'company_id' => $offer->company_id,
            ]);

            return;
        }

        $isAccepted = $this->decision === CompanyOfferDecisionMail::ACCEPTED;
        $customer = $offer->quote?->customer;

        $projectId = $isAccepted
            ? Project::query()->where('company_quotation_id', $offer->id)->value('id')
            : null;

        $details = [
            'companyName' => $company->name ?: 'there',
            'customerName' => $customer?->full_name ?: ($customer?->username ?: 'The customer'),
            'quote_id' => (int) $offer->quote_id,
            'serviceNeeded' => $offer->quote?->service_needed,
            'price' => number_format((float) $offer->price) . ' ' . $offer->currency,
            'actionUrl' => $projectId
                ? route('company.projects.show', $projectId)
                : route('company.quote-requests.show', $offer->quote_id),
        ];

        try {
            Mail::mailer('offer')
                ->to($company->email)
                ->send(new CompanyOfferDecisionMail($this->decision, $details));

            Log::info('Offer decision email accepted by mail transport.', [
                'offer_id' => $offer->id,
                'company_id' => $company->id,
                'decision' => $this->decision,
            ]);
        } catch (Throwable $exception) {
            Log::error('Offer decision email sending failed.', [
                'offer_id' => $offer->id,
                'company_id' => $company->id,
                'decision' => $this->decision,
                'attempt' => $this->attempts(),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('Offer decision email job permanently failed.', [
            'offer_id' => $this->offerId,
            'decision' => $this->decision,
            'exception' => $exception::class,
            'error' => $exception->getMessage(),
        ]);

        report($exception);
    }
}
