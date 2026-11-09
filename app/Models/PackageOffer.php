<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A ready system a company pushes to customers, for loads up to max_load_kw.
 * Accepting it sends the customer's devices to this company as a quote request.
 *
 * @property array<int, array{type: ?string, product_id: ?int, description: string, quantity: float, unit_price: float, total: float}> $items
 */
class PackageOffer extends Model
{
    use SoftDeletes, LogsActivity;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $table = 'tbl_package_offers';

    protected $fillable = [
        'company_id',
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'image_path',
        'image_width',
        'image_height',
        'created_by_type',
        'created_by_id',
        'max_load_kw',
        'currency',
        'items',
        'subtotal',
        'discount',
        'price',
        'expires_at',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'max_load_kw' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'price' => 'decimal:2',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /** Where uploaded offer images are stored (under public/). */
    public const IMAGE_DIR = 'main/images/package-offers';

    /**
     * Validation for the shared offer builder (company Offers page and admin campaign).
     * Fields are nested under $prefix, e.g. package[title_en], package[items][0][quantity].
     */
    public static function rules(string $prefix = 'package'): array
    {
        $p = $prefix . '.';

        return [
            $prefix => ['required', 'array'],
            $p . 'title_en' => ['required', 'string', 'min:3', 'max:150'],
            $p . 'title_ar' => ['nullable', 'string', 'max:150'],
            $p . 'description_en' => ['nullable', 'string', 'max:2000'],
            $p . 'description_ar' => ['nullable', 'string', 'max:2000'],
            $p . 'max_load_kw' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            $p . 'currency' => ['required', 'in:SDG,USD'],
            $p . 'expires_at' => ['nullable', 'date', 'after:now'],
            $p . 'discount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            $p . 'items' => ['required', 'array', 'min:1', 'max:25'],
            $p . 'items.*.description' => ['required', 'string', 'min:2', 'max:255'],
            $p . 'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:10000'],
            $p . 'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            $p . 'items.*.product_id' => ['nullable', 'integer', 'min:1'],
            $p . 'items.*.type' => ['nullable', 'in:' . implode(',', array_keys(Product::TYPES))],
        ];
    }

    /** Readable field names for the builder's errors, e.g. "package.title_en" -> "offer title". */
    public static function attributes(string $prefix = 'package'): array
    {
        $p = $prefix . '.';

        return [
            $p . 'title_en' => __('company/messages.package_offers.title_en'),
            $p . 'title_ar' => __('company/messages.package_offers.title_ar'),
            $p . 'max_load_kw' => __('company/messages.package_offers.max_load'),
            $p . 'currency' => __('company/messages.quote.currency'),
            $p . 'expires_at' => __('company/messages.package_offers.valid_until'),
            $p . 'discount' => __('company/messages.quote.discount'),
            $p . 'items' => __('company/messages.package_offers.items'),
            $p . 'items.*.description' => __('company/messages.quote.item_description'),
            $p . 'items.*.quantity' => __('company/messages.quote.quantity'),
            $p . 'items.*.unit_price' => __('company/messages.quote.unit_price'),
        ];
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id')->withTrashed();
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PackageOfferResponse::class, 'package_offer_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'package_offer_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'package_offer_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Customers can still accept it. */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ACTIVE && !$this->isExpired();
    }

    /** Once pushed, customers hold a copy, so the content is fixed. Close it and make a new one instead. */
    public function isLocked(): bool
    {
        return $this->sent_at !== null;
    }

    /** active | expired | closed, for badges. */
    public function displayStatus(): string
    {
        return match (true) {
            $this->status === self::STATUS_CLOSED => 'closed',
            $this->isExpired() => 'expired',
            default => 'active',
        };
    }

    /** Whole percent saved when there is a discount, e.g. 20. */
    public function discountPercent(): ?int
    {
        $subtotal = (float) $this->subtotal;
        $discount = (float) $this->discount;

        return $subtotal > 0 && $discount > 0 ? (int) round($discount / $subtotal * 100) : null;
    }

    /** Max load as people read it: 5.00 -> "5", 3.50 -> "3.5". */
    public function maxLoadLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->max_load_kw, 2, '.', ''), '0'), '.');
    }

    public function title(string $lang): string
    {
        return $lang === 'ar' && filled($this->title_ar) ? $this->title_ar : $this->title_en;
    }

    public function description(string $lang): ?string
    {
        return $lang === 'ar' && filled($this->description_ar) ? $this->description_ar : $this->description_en;
    }

    /**
     * A load from the app in kW: "4 kW", "4.2kW", "3500 W", "٤ كيلوواط" or a plain number
     * (taken as kW). Null when no number is found.
     */
    public static function loadToKw(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = strtr(trim((string) $value), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.', ',' => '.',
        ]);

        if (!preg_match('/(\d+(?:\.\d+)?)\s*(kw|w|كيلوواط|واط)?/iu', $text, $matches)) {
            return null;
        }

        $number = (float) $matches[1];
        $unit = mb_strtolower($matches[2] ?? '');

        return in_array($unit, ['w', 'واط'], true) ? $number / 1000 : $number;
    }
}
