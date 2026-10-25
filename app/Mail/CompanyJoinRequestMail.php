<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email an admin writes to a solar company (Admin → Send Email), e.g. inviting it to
 * join SunXFinder. Sent from info@sunxfinder.com, which also receives the replies.
 */
class CompanyJoinRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public const FROM_ADDRESS = 'info@sunxfinder.com';

    /** Silent copy of every email sent from this form. */
    public const BCC_ADDRESS = 'mohammed.beng@gmail.com';

    public function __construct(
        public string $emailSubject,
        public string $messageText,
        public string $lang = 'en',
        public ?string $companyName = null,
    ) {
        $this->lang = $lang === 'ar' ? 'ar' : 'en';
    }

    public function build(): self
    {
        return $this
            ->from(self::FROM_ADDRESS, config('mail.from.name', 'SunXFinder'))
            ->replyTo(self::FROM_ADDRESS, config('mail.from.name', 'SunXFinder'))
            ->bcc(self::BCC_ADDRESS)
            ->subject($this->emailSubject)
            ->view("emails.company.{$this->lang}-join-request")
            ->with([
                'subjectLine' => $this->emailSubject,
                'messageText' => $this->messageText,
                'companyName' => $this->companyName,
                'joinUrl' => route('company.register', ['lang' => $this->lang]),
                'websiteUrl' => url('/') . '?lang=' . $this->lang,
                'supportEmail' => self::FROM_ADDRESS,
            ]);
    }
}
