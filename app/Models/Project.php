<?php
// app/Models/Project.php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes, LogsActivity;
    protected $table = 'tbl_projects';


    protected $fillable = [
        'quote_id',
        'company_quotation_id',
        'customer_id',
        'company_id',
        'status',
        'progress_percent',
        'accepted_at',
        'started_at',
        'completed_at',
        'handover_requested_at',
        'handover_confirmed_at',
        'handover_confirmed_by',
        'handover_problem_at',
        'handover_problem_message',
    ];

    /**
     * A finished project the customer neither confirms nor reports a problem on is
     * confirmed automatically this many days after the company finished it
     * (command projects:auto-confirm-handovers, hourly).
     */
    public const AUTO_CONFIRM_DAYS = 7;

    public const CONFIRMED_BY_CUSTOMER = 'customer';
    public const CONFIRMED_BY_AUTO = 'auto';

    /** Sent with the project in the customer API. */
    protected $appends = ['handover_auto_confirm_at'];

    /**
     * Lifecycle: accepted → in_progress → handover_pending → completed.
     * handover_pending: the company finished the job and the customer still has to
     * confirm the handover in the app (POST /quote-complete). Only that completes it.
     */
    public const ACCEPTED = 'accepted';
    public const IN_PROGRESS = 'in_progress';
    public const HANDOVER_PENDING = 'handover_pending';
    public const COMPLETED = 'completed';

    /** Not completed yet: still counts as an active job. */
    public const ACTIVE_STATUSES = [self::ACCEPTED, self::IN_PROGRESS, self::HANDOVER_PENDING];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'handover_requested_at' => 'datetime',
            'handover_confirmed_at' => 'datetime',
            'handover_problem_at' => 'datetime',
        ];
    }

    /** When a waiting handover is confirmed automatically; null unless handover_pending. */
    public function handoverAutoConfirmAt(): ?\Illuminate\Support\Carbon
    {
        return $this->status === self::HANDOVER_PENDING && $this->handover_requested_at
            ? $this->handover_requested_at->copy()->addDays(self::AUTO_CONFIRM_DAYS)
            : null;
    }

    public function getHandoverAutoConfirmAtAttribute(): ?string
    {
        return $this->handoverAutoConfirmAt()?->toISOString();
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public function companyQuotation(): BelongsTo
    {
        return $this->belongsTo(
            CompanyQuotation::class,
            'company_quotation_id'
        );
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id')
            ->orderBy('sort_order');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class, 'project_id')
            ->latest();
    }
}
