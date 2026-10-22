<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailLocale;

    public function __construct(
        public string $code,
        public string $recipientName,
        public string $accountLabel,
        string $locale = 'en'
    ) {
        $this->emailLocale = strtolower($locale) === 'ar' ? 'ar' : 'en';
    }

    public function build(): self
    {
        $isArabic = $this->emailLocale === 'ar';
        $baseUrl = rtrim(config('app.url'), '/');

        return $this
            ->from(
                config('mail.password_reset_from.address'),
                config('mail.password_reset_from.name')
            )
            ->subject(
                $isArabic
                ? 'رمز إعادة تعيين كلمة المرور - SunXFinder'
                : 'Your SunXFinder password reset code'
            )
            ->view($isArabic ? 'emails.auth.reset-password-ar' : 'emails.auth.reset-password-en')
            ->with([
                'code' => $this->code,
                'recipientName' => $this->recipientName,
                'accountLabel' => $this->accountLabel,
                'emailLocale' => $this->emailLocale,
                'homeUrl' => $baseUrl,
                'supportEmail' => config('mail.from.address'),
            ]);
    }
}
