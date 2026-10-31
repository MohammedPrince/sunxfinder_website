<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewCustomerRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array{id:int,name:string,username:string,email:?string,phone:?string,city:?string,address:?string,source:string,registered_at:string} $customer */
    public function __construct(public array $customer)
    {
    }

    public function build(): self
    {
        return $this
            ->subject("New customer sign-up: {$this->customer['name']}")
            ->view('emails.admin.new-customer')
            ->with([
                'customer' => $this->customer,
                'reviewUrl' => route('admin.customers.edit', $this->customer['id']),
                'logoUrl' => rtrim(config('app.url'), '/') . '/visitors/images/sunxfinder-logo-email.png',
            ]);
    }
}
