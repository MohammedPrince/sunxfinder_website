<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A customer's answer to a package offer. Accepting opens a quote request for the company. */
class PackageOfferResponse extends Model
{
    public const ACCEPTED = 'accepted';
    public const DECLINED = 'declined';

    protected $table = 'tbl_package_offer_responses';

    protected $fillable = [
        'package_offer_id',
        'customer_id',
        'notification_id',
        'status',
        'quote_id',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function packageOffer(): BelongsTo
    {
        return $this->belongsTo(PackageOffer::class, 'package_offer_id')->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }
}
