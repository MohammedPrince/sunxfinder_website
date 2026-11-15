<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Asks a company to post a progress update on a stalled project (admin "Email company"
 * on the stalled-projects list). English and Arabic in one message, from the no-reply offer@ address.
 */
class CompanyProjectStalledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $details
    ) {
    }

    public function build(): self
    {
        $projectId = $this->details['projectId'];

        return $this
            ->from(config('mail.offer_from.address'), config('mail.offer_from.name'))
            ->subject("Project #{$projectId} needs an update | المشروع رقم {$projectId} يحتاج إلى تحديث")
            ->view('emails.company.project-stalled')
            ->with($this->details + [
                'supportEmail' => config('mail.from.address'),
            ])
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
                $message->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'All');
            });
    }
}
