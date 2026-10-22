<?php

namespace App\Repositories;

use App\Contracts\SmsSender;
use App\Mail\PasswordResetOtpMail;
use App\Models\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * One-time codes for admin, company and customer accounts.
 * Codes are 6 digits, stored hashed, single use, and expire after EXPIRES_SECONDS.
 *
 * Failed results carry a "reason" the mobile app can switch on:
 * send_failed, no_active_code, expired, too_many_attempts, incorrect_code, invalid_reset_token.
 */
class OtpRepository
{
    public const EXPIRES_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    // After the app verifies a code it gets a reset token, valid long enough to type a new password.
    public const PURPOSE_RESET_TOKEN = 'password_reset_token';

    public const RESET_TOKEN_EXPIRES_SECONDS = 600;

    public static function smsEnabled(): bool
    {
        return (bool) config('services.sms.enabled');
    }

    /** Channels the app may offer the user right now. */
    public static function availableChannels(): array
    {
        return self::smsEnabled() ? ['email', 'sms'] : ['email'];
    }

    /**
     * Create a new code for the account and deliver it. Older unused codes stop working.
     */
    public function sendPasswordResetCode(
        string $type,
        string $email,
        string $recipientName,
        string $accountLabel,
        string $locale = 'en',
        string $channel = 'email',
        ?string $phone = null
    ): array {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $isArabic = $locale === 'ar';

        // Only the newest code is valid.
        $this->invalidate($type, $email, self::PURPOSE_PASSWORD_RESET);

        $otp = Otp::create([
            'type' => $type,
            'email' => $email,
            'code_hash' => Hash::make($code),
            'purpose' => self::PURPOSE_PASSWORD_RESET,
            'channel' => $channel,
            'expires_at' => now()->addSeconds(self::EXPIRES_SECONDS),
            'ip_address' => request()?->ip(),
        ]);

        try {
            if ($channel === 'sms') {
                app(SmsSender::class)->send(
                    (string) $phone,
                    $isArabic
                    ? "رمز إعادة تعيين كلمة المرور في SunXFinder: {$code}. صالح لمدة دقيقة واحدة. لا تشاركه مع أحد."
                    : "Your SunXFinder password reset code is {$code}. It expires in 1 minute. Do not share it."
                );
            } else {
                Mail::mailer('password_reset')
                    ->to($email)
                    ->send(new PasswordResetOtpMail($code, $recipientName, $accountLabel, $locale));
            }
        } catch (Throwable $e) {
            // An unsent code must not stay usable.
            $otp->update(['used_at' => now()]);

            Log::error('SunXFinder password reset: OTP delivery failed', [
                'type' => $type,
                'channel' => $channel,
                'otp_id' => $otp->id,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'reason' => 'send_failed',
                'message' => 'We could not send the verification code right now. Please try again in a moment.',
            ];
        }

        Log::info('SunXFinder password reset: OTP sent', [
            'type' => $type,
            'channel' => $channel,
            'otp_id' => $otp->id,
        ]);

        return [
            'success' => true,
            'message' => $channel === 'sms'
                ? 'We sent a 6-digit code to your phone. It expires in 1 minute.'
                : 'We sent a 6-digit code to your email. It expires in 1 minute.',
            'channel' => $channel,
            'expires_at' => $otp->expires_at,
        ];
    }

    /**
     * Check a code. Wrong guesses are counted; the code is not consumed here.
     */
    public function verifyPasswordResetCode(string $type, string $email, string $code): array
    {
        $otp = $this->latestActive($type, $email, self::PURPOSE_PASSWORD_RESET);

        if (!$otp) {
            return [
                'success' => false,
                'reason' => 'no_active_code',
                'message' => 'No active code was found. Please request a new code.',
            ];
        }

        if ($otp->expires_at->isPast()) {
            $otp->update(['used_at' => now()]);

            return [
                'success' => false,
                'reason' => 'expired',
                'message' => 'This code has expired. Please request a new code.',
            ];
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->update(['used_at' => now()]);

            return [
                'success' => false,
                'reason' => 'too_many_attempts',
                'message' => 'Too many incorrect attempts. Please request a new code.',
            ];
        }

        if (!Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            $remaining = self::MAX_ATTEMPTS - $otp->attempts;

            return [
                'success' => false,
                'reason' => $remaining > 0 ? 'incorrect_code' : 'too_many_attempts',
                'attempts_remaining' => max(0, $remaining),
                'message' => $remaining > 0
                    ? "The code is incorrect. {$remaining} attempt(s) left."
                    : 'Too many incorrect attempts. Please request a new code.',
            ];
        }

        return [
            'success' => true,
            'otp' => $otp,
        ];
    }

    public function consume(Otp $otp): void
    {
        $otp->update(['used_at' => now()]);
    }

    /**
     * Swap a verified code for a reset token (shown to the app once, stored as a SHA-256 hash).
     */
    public function issueResetToken(string $type, string $email): array
    {
        $this->invalidate($type, $email, self::PURPOSE_RESET_TOKEN);

        $token = Str::random(64);

        $row = Otp::create([
            'type' => $type,
            'email' => $email,
            'code_hash' => hash('sha256', $token),
            'purpose' => self::PURPOSE_RESET_TOKEN,
            'channel' => 'token',
            'expires_at' => now()->addSeconds(self::RESET_TOKEN_EXPIRES_SECONDS),
            'ip_address' => request()?->ip(),
        ]);

        return [
            'token' => $token,
            'expires_at' => $row->expires_at,
        ];
    }

    public function verifyResetToken(string $type, string $email, string $token): array
    {
        $row = $this->latestActive($type, $email, self::PURPOSE_RESET_TOKEN);

        if (!$row || $row->expires_at->isPast() || !hash_equals($row->code_hash, hash('sha256', $token))) {
            return [
                'success' => false,
                'reason' => 'invalid_reset_token',
                'message' => 'Your reset session has expired. Please request a new code.',
            ];
        }

        return [
            'success' => true,
            'otp' => $row,
        ];
    }

    private function latestActive(string $type, string $email, string $purpose): ?Otp
    {
        return Otp::query()
            ->where('type', $type)
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();
    }

    private function invalidate(string $type, string $email, string $purpose): void
    {
        Otp::query()
            ->where('type', $type)
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }
}
