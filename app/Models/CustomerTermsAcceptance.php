<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Terms & Conditions acceptance (append-only). Admin only: never part of an API response.
 *
 * @property int $customer_id
 * @property string $terms_version
 * @property string $lang
 * @property string $source
 * @property string|null $platform
 * @property string|null $app_version
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $accepted_at
 */
class CustomerTermsAcceptance extends Model
{
    protected $table = 'tbl_customer_terms_acceptances';

    public const SOURCES = ['login' => 'App sign-in', 'register' => 'App sign-up'];
    public const PLATFORMS = ['android' => 'Android', 'ios' => 'iPhone', 'web' => 'Website'];

    protected $fillable = [
        'customer_id',
        'terms_version',
        'lang',
        'source',
        'platform',
        'app_version',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }
}
