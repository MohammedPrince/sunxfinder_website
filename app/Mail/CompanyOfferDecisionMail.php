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

    public function __construct(
        public string $decision,
        public array $details
    ) {
        $this->decision = $decision === self::ACCEPTED ? self::ACCEPTED : self::REJECTED;
    }

    public function build(): self
    {
        $isAccepted = $this->decision === self::ACCEPTED;
        $baseUrl = rtrim(config('app.url'), '/');
        $quoteId = $this->details['quote_id'] ?? null;

        return $this
            ->from(
                config('mail.offer_from.address'),
                config('mail.offer_from.name')
            )
            ->subject(
                $isAccepted
                ? "Your offer was accepted - Request #{$quoteId} | تم قبول عرضك"
                : "Your offer was not selected - Request #{$quoteId} | لم يتم اختيار عرضك"
            )
            ->view(
                $isAccepted
                ? 'emails.company.customer-accept-offer'
                : 'emails.company.customer-reject-offer'
            )
            ->with($this->details + [
                'homeUrl' => $baseUrl,
                'supportEmail' => config('mail.from.address'),
            ])
            // Mark as automated so mail clients don't send auto-replies to the no-reply box.
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
                $message->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'All');
            });
    }
}
