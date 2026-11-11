<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a company that a customer accepted or rejected its offer.
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

    /** Decisions about a project (the email links to it), not about the offer. */
    public const PROJECT_DECISIONS = [self::COMPLETED, self::AUTO_COMPLETED, self::HANDOVER_PROBLEM];

    public function __construct(
        public string $decision,
        public array $details
    ) {
        $this->decision = in_array($decision, [self::ACCEPTED, ...self::PROJECT_DECISIONS], true) ? $decision : self::REJECTED;
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
                default => "Your offer was not selected - Request #{$quoteId} | لم يتم اختيار عرضك",
            })
            ->view(match ($this->decision) {
                self::ACCEPTED => 'emails.company.customer-accept-offer',
                self::COMPLETED, self::AUTO_COMPLETED, self::HANDOVER_PROBLEM => 'emails.company.customer-confirm-handover',
                default => 'emails.company.customer-reject-offer',
            })
            ->with($this->details + [
                'homeUrl' => $baseUrl,
                'supportEmail' => config('mail.from.address'),
                // Which text the handover email shows.
                'variant' => match ($this->decision) {
                    self::AUTO_COMPLETED => 'auto_completed',
                    self::HANDOVER_PROBLEM => 'problem',
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
