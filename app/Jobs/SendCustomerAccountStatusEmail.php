<?php

namespace App\Jobs;

use App\Mail\CustomerAccountStatusMail;
use App\Models\Customer;
use App\Models\CustomerDeviceToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCustomerAccountStatusEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $customerId, public string $decision)
    {
        $this->decision = $decision === 'approved' ? 'approved' : 'declined';
        $this->afterCommit();
    }

    public function handle(): void
    {
        $customer = Customer::query()->find($this->customerId);
        if (!$customer || $customer->registration_source !== 'website' || !filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $deviceLanguage = CustomerDeviceToken::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('last_used_at')
            ->value('lang');
        $language = strtolower((string) ($deviceLanguage ?: $customer->preferred_language)) === 'ar' ? 'ar' : 'en';

        try {
            Mail::to($customer->email)->send(new CustomerAccountStatusMail(
                (string) ($customer->full_name ?: $customer->username),
                (string) $customer->username,
                $this->decision,
                $language,
            ));
        } catch (Throwable $exception) {
            Log::error('Customer account status email failed.', [
                'customer_id' => $customer->id,
                'decision' => $this->decision,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
