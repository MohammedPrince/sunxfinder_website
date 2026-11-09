<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CustomerDeviceToken;

class Customer extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens, LogsActivity;
    protected $table = 'tbl_customers';

    protected $fillable = [
        'username',
        'password',
        'full_name',
        'phone',
        'email',
        'gender',
        'city',
        'address',
        'status',
        'is_active',
        'registration_source',
        'preferred_language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // Only GET /pending-load sends it.
        'pending_load',
        // Admin only (System → Terms Acceptance).
        'terms_accepted_version',
        'terms_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'pending_load' => 'array',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'app_status_changed_at' => 'datetime',
            'max_open_quotes' => 'integer',
        ];
    }

    /** Every Terms acceptance (Admin → System → Terms Acceptance). */
    public function termsAcceptances(): HasMany
    {
        return $this->hasMany(CustomerTermsAcceptance::class, 'customer_id');
    }

    public function latestTermsAcceptance(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CustomerTermsAcceptance::class, 'customer_id')->latestOfMany();
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(CustomerDeviceToken::class, 'customer_id');
    }

}
