<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

/**
 * Placeholder SMS driver until an SMS provider is connected.
 * Writes to the log only; the number is masked and the message body is not logged.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $message): void
    {
        Log::info('SunXFinder SMS (log driver, not delivered)', [
            'phone' => substr($phone, 0, 3) . str_repeat('*', max(0, strlen($phone) - 5)) . substr($phone, -2),
            'length' => mb_strlen($message),
        ]);
    }
}
