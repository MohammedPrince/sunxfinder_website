<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * A message sent from the website "Contact us" form to info@sunxfinder.com.
 * Reply-To is the visitor, so the team can answer straight from the inbox.
 */
class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $contact
    ) {
    }

    public function build(): self
    {
        return $this
            ->replyTo($this->contact['email'], $this->contact['name'])
            ->subject('Website contact: ' . $this->contact['subject'])
            ->view('emails.contact-message')
            ->with([
                'contact' => $this->contact,
                'homeUrl' => rtrim(config('app.url'), '/'),
            ]);
    }
}
