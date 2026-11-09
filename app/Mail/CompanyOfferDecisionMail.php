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

    public function __construct(
        public string $decision,
        public array $details
    ) {
        $this->decision = in_array($decision, [self::ACCEPTED, self::COMPLETED], true) ? $decision : self::REJECTED;
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
                default => "Your offer was not selected - Request #{$quoteId} | لم يتم اختيار عرضك",
            })
            ->view(match ($this->decision) {
                self::ACCEPTED => 'emails.company.customer-accept-offer',
                self::COMPLETED => 'emails.company.customer-confirm-handover',
                default => 'emails.company.customer-reject-offer',
            })
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
