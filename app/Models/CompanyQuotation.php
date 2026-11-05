<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Decimal columns come back as strings ("200000.00") and accept floats or numeric strings.
 *
 * @property string|float $price
 * @property string|float $commission_rate
 * @property string|float $commission_amount
 * @property string|null $commission_type
 */
class CompanyQuotation extends Model
{
    use SoftDeletes, LogsActivity;
    protected $table = 'tbl_company_quotations';

    protected $fillable = [
        'quote_id',
        'company_id',
        'price',
        'currency',
        'message',
        'ar_message',
        'status',
    ];

    /** Commission follows the company's rate (default), or the admin set it for this offer. */
    public const COMMISSION_COMPANY = 'company';
    public const COMMISSION_PERCENT = 'percent';
    public const COMMISSION_FIXED = 'fixed';
    public const COMMISSION_TYPES = [self::COMMISSION_COMPANY, self::COMMISSION_PERCENT, self::COMMISSION_FIXED];

    /** SunXFinder commission is between the admin and the company; never sent to customers. */
    protected $hidden = [
        'commission_type',
        'commission_rate',
        'commission_amount',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    /**
     * Works out commission_rate and commission_amount from the current price (does not save).
     *  company: the company's rate now
     *  percent: keeps the admin's rate for this offer
     *  fixed:   keeps the admin's amount (never more than the price); the rate is what it comes to
     */
    public function applyCommission(float $companyRate): static
    {
        $price = (float) $this->price;
        $type = $this->commission_type ?: self::COMMISSION_COMPANY;

        if ($type === self::COMMISSION_FIXED) {
            $amount = min((float) $this->commission_amount, $price);
            $this->commission_amount = self::toDecimal($amount);
            $this->commission_rate = self::toDecimal($price > 0 ? $amount / $price * 100 : 0);

            return $this;
        }

        $rate = $type === self::COMMISSION_PERCENT ? (float) $this->commission_rate : $companyRate;
        $this->commission_type = $type;
        $this->commission_rate = self::toDecimal($rate);
        $this->commission_amount = self::toDecimal($price * $rate / 100);

        return $this;
    }

    /** Money/percent as the decimal:2 string the casts store, e.g. 200000 -> "200000.00". */
    private static function toDecimal(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }

    public function hasCustomCommission(): bool
    {
        return in_array($this->commission_type, [self::COMMISSION_PERCENT, self::COMMISSION_FIXED], true);
    }

    /** Rate as shown to people: 1.00 -> "1", 2.50 -> "2.5". */
    public function commissionRateLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->commission_rate, 2, '.', ''), '0'), '.');
    }

    /**
     * Original customer quote request.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    /**
     * Company that submitted this offer.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    /**
     * Invoice issued with this offer, if the company created one.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'company_quotation_id');
    }

    /**
     * Scope offers that are still available for selection.
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope offers selected by the customer.
     */
    public function scopeSelected($query)
    {
        return $query->where('status', 'selected');
    }
}
