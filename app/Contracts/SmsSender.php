<?php

namespace App\Contracts;

/**
 * Sends text messages. Bound in AppServiceProvider from config('services.sms.driver').
 * To connect a real SMS provider: implement this interface and add its driver name there.
 */
interface SmsSender
{
    /**
     * @param string $phone   Recipient phone number (as stored on the customer).
     * @param string $message Plain-text message body.
     *
     * @throws \Throwable when the message could not be sent.
     */
    public function send(string $phone, string $message): void;
}
