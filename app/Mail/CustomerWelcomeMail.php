<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class CustomerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $emailLocale;

    public function __construct(
        string $customerName,
        string $locale = 'en'
    ) {
        $this->customerName = $customerName;
        $this->emailLocale = strtolower($locale) === 'ar' ? 'ar' : 'en';
    }

    public function build(): self
    {
        $isArabic = $this->emailLocale === 'ar';
        $baseUrl = rtrim(config('app.url'), '/');

        $view = $isArabic
            ? 'emails.customer.welcome-ar'
            : 'emails.customer.welcome-en';

        Log::info('SunXFinder welcome email: preparing template', [
            'locale' => $this->emailLocale,
            'view' => $view,
        ]);

        return $this
            ->subject(
                $isArabic
                ? 'مرحباً بك في SunXFinder'
                : 'Welcome to SunXFinder'
            )
            ->view($view)
            ->with([
                'customerName' => $this->customerName,
                'homeUrl' => $baseUrl,
                'logoUrl' => $baseUrl . '/visitors/images/sun.png',
                'supportEmail' => config('mail.from.address'),
                'privacyUrl' => url('/privacy-policy'),
                'termsUrl' => url('/terms'),
            ]);
    }
}