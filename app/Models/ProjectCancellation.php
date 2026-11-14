<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A company backing out of a deal the customer accepted (App\Support\OfferWithdrawal).
 *
 *  withdrawn  done right away: the project had not started (status "accepted")
 *  requested  waits for an admin: work had started, or the company withdrew too often lately
 *  approved   an admin agreed; the project was cancelled like a withdrawal
 *  rejected   an admin said no; the project carries on
 *  cancelled  the company took its request back
 *
 * The project is soft-deleted when cancelled, so this row is the history of what happened.
 *
 * @property string $status
 * @property string $reason
 * @property bool $counts_against_company
 */
class ProjectCancellation extends Model
{
    protected $table = 'tbl_project_cancellations';

    public const WITHDRAWN = 'withdrawn';
    public const REQUESTED = 'requested';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    /** Statuses that ended the deal. */
    public const DONE_STATUSES = [self::WITHDRAWN, self::APPROVED];

    /**
     * More than this many counted withdrawals within WINDOW_DAYS flags the company: it can no
     * longer withdraw by itself (an admin decides) and it is listed lower in the app.
     */
    public const FLAG_THRESHOLD = 3;
    public const WINDOW_DAYS = 30;

    /** Why a company backs out. "other" needs a note. */
    public const REASONS = [
        'price_changed' => ['en' => 'Prices or the exchange rate changed', 'ar' => 'تغيّرت الأسعار أو سعر الصرف'],
        'out_of_stock' => ['en' => 'The equipment is out of stock', 'ar' => 'المعدات غير متوفرة حالياً'],
        'outside_coverage' => ['en' => 'The site is outside the company\'s service area', 'ar' => 'الموقع خارج نطاق خدمة الشركة'],
        'site_not_suitable' => ['en' => 'The site is not suitable for this system', 'ar' => 'الموقع غير مناسب لهذه المنظومة'],
        'customer_unreachable' => ['en' => 'The company could not reach the customer', 'ar' => 'تعذّر على الشركة التواصل مع العميل'],
        'no_capacity' => ['en' => 'The company cannot take on the job at this time', 'ar' => 'لا تستطيع الشركة تنفيذ العمل في الوقت الحالي'],
        'other' => ['en' => 'Other reason', 'ar' => 'سبب آخر'],
    ];

    /** Set by the system when the customer declines a price change; not offered to companies. */
    public const REASON_PRICE_DECLINED = 'customer_declined_price';

    public const SYSTEM_REASONS = [
        self::REASON_PRICE_DECLINED => ['en' => 'The customer declined the new price', 'ar' => 'رفض العميل السعر الجديد'],
    ];

    protected $fillable = [
        'project_id',
        'quote_id',
        'company_quotation_id',
        'company_id',
        'customer_id',
        'stage',
        'progress_percent',
        'reason',
        'note',
        'status',
        'counts_against_company',
        'reopened_offers',
        'admin_id',
        'admin_note',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'counts_against_company' => 'boolean',
            'progress_percent' => 'integer',
            'reopened_offers' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public static function reasonLabel(?string $reason, string $lang = 'en'): string
    {
        $lang = $lang === 'ar' ? 'ar' : 'en';
        $labels = self::REASONS + self::SYSTEM_REASONS;

        return $labels[$reason][$lang] ?? ($labels['other'][$lang]);
    }

    public function reasonText(string $lang = 'en'): string
    {
        return self::reasonLabel($this->reason, $lang);
    }

    public function isDone(): bool
    {
        return in_array($this->status, self::DONE_STATUSES, true);
    }

    /** Withdrawals that count against companies, within the last WINDOW_DAYS. */
    public function scopeCountedRecently(Builder $query): Builder
    {
        return $query->whereIn('status', self::DONE_STATUSES)
            ->where('counts_against_company', true)
            ->where('created_at', '>=', now()->subDays(self::WINDOW_DAYS));
    }

    public static function recentCountFor(int $companyId): int
    {
        return self::query()->where('company_id', $companyId)->countedRecently()->count();
    }

    public static function isCompanyFlagged(int $companyId): bool
    {
        return self::recentCountFor($companyId) >= self::FLAG_THRESHOLD;
    }

    /**
     * SQL for "this company is flagged" on tbl_solar_companies, to list flagged companies
     * lower in the app. One indexed count per company row.
     *
     * @return array{0: string, 1: array}
     */
    public static function flaggedCompanySql(string $companyIdColumn = 'tbl_solar_companies.id'): array
    {
        return [
            '(SELECT COUNT(*) FROM tbl_project_cancellations pc WHERE pc.company_id = ' . $companyIdColumn
                . ' AND pc.status IN (?, ?) AND pc.counts_against_company = 1 AND pc.created_at >= ?) >= ?',
            [self::WITHDRAWN, self::APPROVED, now()->subDays(self::WINDOW_DAYS), self::FLAG_THRESHOLD],
        ];
    }

    public function project(): BelongsTo
    {
        // Cancelled projects are soft-deleted.
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id')->withTrashed();
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(CompanyQuotation::class, 'company_quotation_id')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
