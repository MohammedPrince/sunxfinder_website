<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerAccountStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customerName,
        public string $username,
        public string $decision,
        public string $emailLocale = 'en',
    ) {
        $this->decision = $decision === 'approved' ? 'approved' : 'declined';
        $this->emailLocale = strtolower($emailLocale) === 'ar' ? 'ar' : 'en';
    }

    public function build(): self
    {
        $isArabic = $this->emailLocale === 'ar';
        $subject = $isArabic
            ? ($this->decision === 'approved' ? 'تمت الموافقة على حسابك في SunXFinder' : 'تحديث بشأن طلب حسابك في SunXFinder')
            : ($this->decision === 'approved' ? 'Your SunXFinder account is approved' : 'Update on your SunXFinder account');

        return $this
            ->subject($subject)
            ->view('emails.customer.account-status')
            ->with([
                'customerName' => $this->customerName,
                'username' => $this->username,
                'decision' => $this->decision,
                'emailLocale' => $this->emailLocale,
                'homeUrl' => rtrim(config('app.url'), '/'),
                'logoUrl' => rtrim(config('app.url'), '/') . '/visitors/images/sunxfinder-logo-email.png',
            ]);
    }
}
