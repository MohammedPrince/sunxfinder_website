<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDeviceToken extends Model
{
    protected $table = 'tbl_customer_device_tokens';

    protected $fillable = [
        'customer_id',
        'fcm_token',
        'token_hash',
        'platform',
        'lang',
        'device_name',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
