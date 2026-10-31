<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
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
        'offer' => 'Company offer',
        'new_company' => 'New company',
        'promotion' => 'Promotion / news',
    ];

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
        'audience_type',
        'audience_city',
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

    public function recipients(): HasMany
    {
        return $this->hasMany(
            CustomerNotification::class,
            'notification_id'
        );
    }
}
