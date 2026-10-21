<?php

namespace App\Jobs;

use App\Mail\CustomerWelcomeMail;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCustomerWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $customerId,
        public string $locale = 'en',
    ) {
        $this->locale = strtolower($locale) === 'ar' ? 'ar' : 'en';
        $this->afterCommit();
    }

    public function handle(): void
    {
        Log::info('Welcome email job started.', [
            'customer_id' => $this->customerId,
            'locale' => $this->locale,
            'attempt' => $this->attempts(),
        ]);

        $customer = Customer::find($this->customerId);

        if (!$customer) {
            Log::warning('Welcome email skipped: customer not found.', [
                'customer_id' => $this->customerId,
            ]);

            return;
        }

        if (empty($customer->email)) {
            Log::warning('Welcome email skipped: customer has no email.', [
                'customer_id' => $customer->id,
            ]);

            return;
        }

        $customerName = $customer->full_name
            ?? $customer->name
            ?? $customer->username
            ?? 'Customer';

        Log::info('Attempting to send welcome email.', [
            'customer_id' => $customer->id,
            'locale' => $this->locale,
            'mailer' => config('mail.default'),
            'smtp_host' => config('mail.mailers.smtp.host'),
            'smtp_port' => config('mail.mailers.smtp.port'),
        ]);

        try {
            Mail::to($customer->email)->send(
                new CustomerWelcomeMail(
                    (string) $customerName,
                    $this->locale
                )
            );

            Log::info('Welcome email accepted by mail transport.', [
                'customer_id' => $customer->id,
                'locale' => $this->locale,
            ]);
        } catch (Throwable $exception) {
            Log::error('Welcome email sending failed.', [
                'customer_id' => $customer->id,
                'locale' => $this->locale,
                'attempt' => $this->attempts(),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('Welcome email job permanently failed.', [
            'customer_id' => $this->customerId,
            'locale' => $this->locale,
            'exception' => $exception::class,
            'error' => $exception->getMessage(),
        ]);

        report($exception);
    }
}
