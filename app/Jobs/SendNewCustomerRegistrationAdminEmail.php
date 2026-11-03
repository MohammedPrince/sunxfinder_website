<?php

namespace App\Jobs;

use App\Mail\NewCustomerRegisteredMail;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewCustomerRegistrationAdminEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $customerId, public string $source)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $customer = Customer::query()->find($this->customerId);
        $adminEmail = config('mail.admin_notify_address');

        if (!$customer || !$adminEmail) {
            return;
        }

        Mail::to($adminEmail)->send(new NewCustomerRegisteredMail([
            'id' => (int) $customer->id,
            'name' => (string) ($customer->full_name ?: $customer->username),
            'username' => (string) $customer->username,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'city' => $customer->city,
            'address' => $customer->address,
            'source' => $this->source,
            'registered_at' => $customer->created_at?->format('M d, Y H:i') ?? now()->format('M d, Y H:i'),
        ]));
    }
}
