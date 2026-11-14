<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a company that a customer accepted or rejected its offer, about its project, and about
 * changes to an accepted deal (reopened request, price change answer, cancellation decision).
 * Sent from the no-reply offer@ address.
 */
class CompanyOfferDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
    // The customer confirmed the handover in the app: the project is completed.
    public const COMPLETED = 'completed';
    // Nobody answered within Project::AUTO_CONFIRM_DAYS, so the handover was confirmed automatically.
    public const AUTO_COMPLETED = 'auto_completed';
    // The customer reported a problem instead of confirming: the project is back in progress.
    public const HANDOVER_PROBLEM = 'handover_problem';

    // Another company left the deal, so this company's offer can be chosen again.
    public const REOPENED = 'reopened';
    // The customer accepted / declined this company's price change on the accepted offer.
    public const PRICE_ACCEPTED = 'price_accepted';
    public const PRICE_DECLINED = 'price_declined';
    // An admin decided on this company's cancellation request.
    public const CANCEL_APPROVED = 'cancel_approved';
    public const CANCEL_REJECTED = 'cancel_rejected';

    /** Decisions about a project (the email links to it), not about the offer. */
    public const PROJECT_DECISIONS = [self::COMPLETED, self::AUTO_COMPLETED, self::HANDOVER_PROBLEM, self::PRICE_ACCEPTED, self::CANCEL_REJECTED];

    /** Changes to an accepted deal; one template (emails.company.deal-change). */
    public const DEAL_CHANGES = [self::REOPENED, self::PRICE_ACCEPTED, self::PRICE_DECLINED, self::CANCEL_APPROVED, self::CANCEL_REJECTED];

    public function __construct(
        public string $decision,
        public array $details
    ) {
        $known = [self::ACCEPTED, self::COMPLETED, self::AUTO_COMPLETED, self::HANDOVER_PROBLEM, ...self::DEAL_CHANGES];
        $this->decision = in_array($decision, $known, true) ? $decision : self::REJECTED;
    }

    public function build(): self
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $quoteId = $this->details['quote_id'] ?? null;

        return $this
            ->from(
                config('mail.offer_from.address'),
                config('mail.offer_from.name')
            )
            ->subject(match ($this->decision) {
                self::ACCEPTED => "Your offer was accepted - Request #{$quoteId} | تم قبول عرضك",
                self::COMPLETED => "Customer confirmed the handover - Request #{$quoteId} | أكّد العميل الاستلام",
                self::AUTO_COMPLETED => "Handover confirmed automatically - Request #{$quoteId} | تم تأكيد الاستلام تلقائياً",
                self::HANDOVER_PROBLEM => "Customer reported a problem - Request #{$quoteId} | أبلغ العميل عن مشكلة",
                self::REOPENED => "Request #{$quoteId} is open again - your offer can be chosen | الطلب مفتوح من جديد",
                self::PRICE_ACCEPTED => "Customer accepted the new price - Request #{$quoteId} | قبل العميل السعر الجديد",
                self::PRICE_DECLINED => "Customer declined the new price - Request #{$quoteId} | رفض العميل السعر الجديد",
                self::CANCEL_APPROVED => "Cancellation approved - Request #{$quoteId} | تمت الموافقة على الإلغاء",
                self::CANCEL_REJECTED => "Cancellation not approved - Request #{$quoteId} | لم تتم الموافقة على الإلغاء",
                default => "Your offer was not selected - Request #{$quoteId} | لم يتم اختيار عرضك",
            })
            ->view(match ($this->decision) {
                self::ACCEPTED => 'emails.company.customer-accept-offer',
                self::COMPLETED, self::AUTO_COMPLETED, self::HANDOVER_PROBLEM => 'emails.company.customer-confirm-handover',
                self::REOPENED, self::PRICE_ACCEPTED, self::PRICE_DECLINED, self::CANCEL_APPROVED, self::CANCEL_REJECTED => 'emails.company.deal-change',
                default => 'emails.company.customer-reject-offer',
            })
            ->with($this->details + [
                'homeUrl' => $baseUrl,
                'supportEmail' => config('mail.from.address'),
                // Which text the handover email shows.
                'variant' => match ($this->decision) {
                    self::AUTO_COMPLETED => 'auto_completed',
                    self::HANDOVER_PROBLEM => 'problem',
                    self::REOPENED, self::PRICE_ACCEPTED, self::PRICE_DECLINED, self::CANCEL_APPROVED, self::CANCEL_REJECTED => $this->decision,
                    default => 'completed',
                },
            ])
            // Mark as automated so mail clients don't send auto-replies to the no-reply box.
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
                $message->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'All');
            });
    }
}
