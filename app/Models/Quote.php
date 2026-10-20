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
        'service_needed',
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