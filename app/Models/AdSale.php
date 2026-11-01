<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdSale extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tbl_ad_sales';

    public const PRODUCTS = [
        'slider' => 'Home slider',
        'carousel' => 'Company carousel',
        'campaign' => 'Campaign push',
        'all_customers' => 'Push to all customers',
        'pack' => 'Package',
        'other' => 'Other',
    ];

    /** What a company may ask for. Packages and other deals are arranged by admin. */
    public const REQUESTABLE_PRODUCTS = ['slider', 'carousel', 'campaign', 'all_customers'];

    public const STATUSES = [
        'requested' => 'Requested',
        'awaiting_payment' => 'Awaiting payment',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ];

    public const PLATFORMS = ['app' => 'Mobile app', 'website' => 'Website', 'both' => 'App and website'];

    public const ENDING_SOON_DAYS = 7;

    protected $fillable = [
        'company_id', 'client_name', 'product', 'platform', 'app_slider_id', 'company_carousel_id',
        'link_url', 'artwork_path', 'request_note', 'amount', 'currency', 'starts_on', 'ends_on',
        'status', 'paid_at', 'payment_reference', 'admin_notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public function slider(): BelongsTo
    {
        return $this->belongsTo(AppSlider::class, 'app_slider_id')->withTrashed();
    }

    public function carousel(): BelongsTo
    {
        return $this->belongsTo(CompanyCarousel::class, 'company_carousel_id')->withTrashed();
    }

    /** Paid ads that end within the warning window. */
    public function scopeEndingSoon($query)
    {
        return $query->where('status', 'paid')
            ->whereBetween('ends_on', [today(), today()->addDays(self::ENDING_SOON_DAYS)]);
    }

    public function clientLabel(): string
    {
        return $this->company?->name ?: ($this->client_name ?: '—');
    }

    public function isEndingSoon(): bool
    {
        return $this->status === 'paid'
            && $this->ends_on
            && $this->ends_on->betweenIncluded(today(), today()->addDays(self::ENDING_SOON_DAYS));
    }

    /** The tracked ad behind this sale, as [type, id], or null for pushes and packages. */
    public function trackedAd(): ?array
    {
        return match (true) {
            $this->app_slider_id !== null => ['slider', (int) $this->app_slider_id],
            $this->company_carousel_id !== null => ['carousel', (int) $this->company_carousel_id],
            default => null,
        };
    }

    public function artworkUrl(): ?string
    {
        return $this->artwork_path ? asset($this->artwork_path) : null;
    }
}
