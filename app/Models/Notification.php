<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
     use SoftDeletes, LogsActivity;
    protected $table = 'tbl_notifications';

    /** Types sent from the admin "Customer campaign" composer. */
    public const CAMPAIGN_TYPES = [
        'offer' => 'Special offer',
        'new_company' => 'New company',
        'promotion' => 'Promotion / news',
    ];

    /** Plain notifications are deleted (soft) this many days after they were sent. */
    public const AUTO_DELETE_DAYS = 5;

    /**
     * Plain notifications older than AUTO_DELETE_DAYS, sent by admin or by a company.
     * Kept for good: campaigns (the same rule as isCampaign()), sponsored sends and package
     * offers. The monthly sponsored-send limit counts with withTrashed(), so it is unaffected.
     */
    public function scopeAutoDeletable(Builder $query): Builder
    {
        $cutoff = now()->subDays(self::AUTO_DELETE_DAYS);

        return $query
            ->where(fn(Builder $q) => $q
                ->where('published_at', '<', $cutoff)
                ->orWhere(fn(Builder $q) => $q->whereNull('published_at')->where('created_at', '<', $cutoff)))
            ->where(fn(Builder $q) => $q->whereNull('is_sponsored')->orWhere('is_sponsored', false))
            ->whereNull('company_id')
            ->whereNull('package_offer_id')
            ->where(fn(Builder $q) => $q->whereNull('image_path')->orWhere('image_path', ''))
            ->where(fn(Builder $q) => $q->whereNull('link_url')->orWhere('link_url', ''))
            ->where(fn(Builder $q) => $q->whereNull('format')->orWhere('format', '<>', 'html'))
            ->whereNotIn('type', array_keys(self::CAMPAIGN_TYPES));
    }

    protected $fillable = [
        'sender_type',
        'sender_id',
        'type',
        'title_en',
        'title_ar',
        'message_en',
        'message_ar',
        'format',
        'image_path',
        'image_width',
        'image_height',
        'link_url',
        'link_label_en',
        'link_label_ar',
        'company_id',
        'quote_id',
        'offer_title_en',
        'offer_title_ar',
        'offer_price',
        'offer_old_price',
        'offer_currency',
        'offer_expires_at',
        'package_offer_id',
        'notification_schedule_id',
        'audience_type',
        'audience_city',
        'is_sponsored',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'offer_expires_at' => 'datetime',
            'offer_price' => 'decimal:2',
            'offer_old_price' => 'decimal:2',
        ];
    }

    // sender_id points to a company or an admin depending on sender_type.
    public function senderCompany(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'sender_id')->withTrashed();
    }

    public function senderAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sender_id');
    }

    /** Company featured in a campaign (offer / new company). */
    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id')->withTrashed();
    }

    /** Display name of whoever published it: the company name, or "Admin · name". */
    public function senderLabel(): string
    {
        if ($this->sender_type === 'company') {
            return $this->senderCompany?->name ?? 'Company #' . $this->sender_id;
        }

        // tbl_admins has a username but no name column.
        $adminName = $this->senderAdmin?->username;

        return $adminName ? 'Admin · ' . $adminName : 'Admin';
    }

    public function isCampaign(): bool
    {
        return $this->format === 'html'
            || $this->image_path
            || $this->link_url
            || $this->company_id
            || array_key_exists($this->type, self::CAMPAIGN_TYPES);
    }

    /** Company package offer the customer can accept or decline from this notification. */
    public function packageOffer(): BelongsTo
    {
        return $this->belongsTo(PackageOffer::class, 'package_offer_id')->withTrashed();
    }

    /**
     * Offer card fields copied from a package offer, so the card matches what was sent.
     * The offer's image becomes the notification image (a campaign may upload its own instead).
     */
    public static function offerCardFields(PackageOffer $offer): array
    {
        $image = $offer->image_path ? [
            'image_path' => $offer->image_path,
            'image_width' => $offer->image_width,
            'image_height' => $offer->image_height,
        ] : [];

        return $image + [
            'company_id' => $offer->company_id,
            'package_offer_id' => $offer->id,
            'offer_title_en' => $offer->title_en,
            'offer_title_ar' => $offer->title_ar,
            'offer_price' => $offer->price,
            'offer_old_price' => (float) $offer->discount > 0 ? $offer->subtotal : null,
            'offer_currency' => $offer->currency,
            'offer_expires_at' => $offer->expires_at,
        ];
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    /** Discount in whole percent when both prices are set, e.g. 20 for 1,000 → 800. */
    public function offerDiscountPercent(): ?int
    {
        $price = (float) $this->offer_price;
        $old = (float) $this->offer_old_price;

        return $price > 0 && $old > $price ? (int) round(($old - $price) / $old * 100) : null;
    }

    /** Automatic notification schedule that sent this, if any. */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(NotificationSchedule::class, 'notification_schedule_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(
            CustomerNotification::class,
            'notification_id'
        );
    }
}
