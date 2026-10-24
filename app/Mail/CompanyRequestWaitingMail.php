<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Reminds a company that a customer request is waiting for its offer
 * (admin "Nudge companies" or "Reassign"). Sent from the no-reply offer@ address.
 */
class CompanyRequestWaitingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $details
    ) {
    }

    public function build(): self
    {
        $reference = $this->details['reference'];

        return $this
            ->from(config('mail.offer_from.address'), config('mail.offer_from.name'))
            ->subject("A customer is waiting for your offer - {$reference} | عميل بانتظار عرضك")
            ->view('emails.company.request-waiting')
            ->with($this->details + [
                'supportEmail' => config('mail.from.address'),
            ])
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
                $message->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'All');
            });
    }
}
