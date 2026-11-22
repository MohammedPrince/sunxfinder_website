<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The company asks to change the price of an offer the customer accepted, before work starts.
 * The customer accepts (the offer gets the new price) or declines (the deal ends and the
 * request reopens for other offers). See App\Support\OfferWithdrawal.
 *
 * @property string|float $old_price
 * @property string|float $new_price
 */
class OfferPriceChange extends Model
{
    protected $table = 'tbl_offer_price_changes';

    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const DECLINED = 'declined';
    public const CANCELLED = 'cancelled';

    public const REASONS = [
        'price_changed' => ['en' => 'Prices or the exchange rate changed', 'ar' => 'تغيّرت الأسعار أو سعر الصرف'],
        'equipment_changed' => ['en' => 'The equipment had to change', 'ar' => 'اضطرت الشركة لتغيير المعدات'],
        'site_inspection' => ['en' => 'The site inspection showed more work is needed', 'ar' => 'أظهرت معاينة الموقع أن العمل أكبر من المتوقع'],
        'other' => ['en' => 'Other reason', 'ar' => 'سبب آخر'],
    ];

    protected $fillable = [
        'company_quotation_id',
        'project_id',
        'quote_id',
        'company_id',
        'customer_id',
        'old_price',
        'new_price',
        'currency',
        'reason',
        'note',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'responded_at' => 'datetime',
        ];
    }

    public static function reasonLabel(?string $reason, string $lang = 'en'): string
    {
        $lang = $lang === 'ar' ? 'ar' : 'en';

        return self::REASONS[$reason][$lang] ?? self::REASONS['other'][$lang];
    }

    /** Change in percent of the old price, e.g. 12.5 or -4. */
    public function changePercent(): float
    {
        $old = (float) $this->old_price;

        return $old > 0 ? round(((float) $this->new_price - $old) / $old * 100, 1) : 0.0;
    }

    /** Shape sent to the app (My Quotes and project progress). */
    public function toApi(): array
    {
        return [
            'id' => (int) $this->id,
            'offer_id' => (int) $this->company_quotation_id,
            'project_id' => (int) $this->project_id,
            'company_id' => (int) $this->company_id,
            'company_name' => $this->company?->name,
            'old_price' => (float) $this->old_price,
            'new_price' => (float) $this->new_price,
            'currency' => $this->currency,
            'change_percent' => $this->changePercent(),
            'reason' => $this->reason,
            'reason_label_en' => self::reasonLabel($this->reason, 'en'),
            'reason_label_ar' => self::reasonLabel($this->reason, 'ar'),
            'note' => $this->note,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(CompanyQuotation::class, 'company_quotation_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }
}
