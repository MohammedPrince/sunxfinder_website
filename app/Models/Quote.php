<?php

namespace App\Models;

use App\Models\CompanyQuotation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Quote extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_quote_requests';

    protected $fillable = [
        'customer_id',
        'company_id',
        'quote_name',
        'estimated_load',
        'peak_load',
        'daily_load',
        'message',
        'status',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public function companyQuotations(): HasMany
    {
        return $this->hasMany(CompanyQuotation::class, 'quote_id');
    }
    public function companyQuotationsWithStatus(): HasMany
    {
        return $this->hasMany(CompanyQuotation::class, 'quote_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'quote_id', 'id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class, 'quote_id', 'id');
    }

    /**
     * Where this request stands, in plain words, for list badges and the journey tracker.
     * Uses the loaded "project" relation and the offer count when available.
     *
     * @return array{key: string, label: string, class: string}
     */
    public function stage(?int $offersCount = null): array
    {
        $offersCount ??= $this->company_quotations_count
            ?? ($this->relationLoaded('companyQuotations') ? $this->companyQuotations->count() : 0);

        $project = $this->relationLoaded('project') ? $this->project : null;

        return match (true) {
            $this->status === 'cancelled' => ['key' => 'cancelled', 'label' => 'Cancelled by customer', 'class' => 'pill-purple'],
            $this->status === 'rejected' => ['key' => 'rejected', 'label' => 'Rejected', 'class' => 'pill-red'],
            $this->status === 'completed' || $project?->status === 'completed' => ['key' => 'completed', 'label' => 'Completed', 'class' => 'pill-green'],
            $project?->status === 'in_progress' => ['key' => 'installing', 'label' => 'Installation in progress', 'class' => 'pill-blue'],
            $this->status === 'accepted' || $project !== null => ['key' => 'accepted', 'label' => 'Offer accepted · not started', 'class' => 'pill-orange'],
            $offersCount > 0 => ['key' => 'comparing', 'label' => 'Customer comparing offers', 'class' => 'pill-yellow'],
            default => ['key' => 'waiting', 'label' => 'Waiting for offers', 'class' => 'pill-orange'],
        };
    }

    /**
     * Requests a company can see: assigned to it, open to all companies,
     * or requests it has already submitted an offer on.
     */
    public function scopeVisibleToCompany($query, int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->orWhereNull('company_id')
                ->orWhereHas('companyQuotations', function ($quotationQuery) use ($companyId) {
                    $quotationQuery->where('company_id', $companyId);
                });
        });
    }
}