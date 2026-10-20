<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyQuotation extends Model
{
    use SoftDeletes;
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

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
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