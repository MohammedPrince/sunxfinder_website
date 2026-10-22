<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CustomerDeviceToken;

class Customer extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens;
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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(CustomerDeviceToken::class, 'customer_id');
    }
}
