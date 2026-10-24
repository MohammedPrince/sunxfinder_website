<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyCarousel extends Model
{

    use SoftDeletes, LogsActivity;
    protected $table = 'tbl_company_carousels';

    protected $fillable = ['company_id', 'platform', 'sort_order', 'interval_seconds', 'is_active', 'starts_at', 'ends_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'interval_seconds' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public function scopeCurrentlyVisible($query)
    {
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
