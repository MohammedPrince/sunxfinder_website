<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $table = 'tbl_otps';

    public const TYPES = ['admin', 'company', 'customer'];

    public const CHANNELS = ['email', 'sms'];

    protected $fillable = [
        'type',
        'email',
        'code_hash',
        'purpose',
        'channel',
        'attempts',
        'expires_at',
        'used_at',
        'ip_address',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
