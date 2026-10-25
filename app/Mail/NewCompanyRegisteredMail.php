<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the admin that a solar company signed up on the website and is waiting for
 * approval. Queued after the sign-up, so a mail problem never blocks registration.
 */
class NewCompanyRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array{id:int,name:string,username:string,email:?string,phone:?string,city:?string,registered_at:string} $company */
    public function __construct(public array $company)
    {
    }

    public function build(): self
    {
        return $this
            ->subject("New company sign-up: {$this->company['name']}")
            ->view('emails.admin.new-company')
            ->with([
                'company' => $this->company,
                'reviewUrl' => route('admin.companies.edit', $this->company['id']),
            ]);
    }
}
