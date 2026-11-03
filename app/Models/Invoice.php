<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tbl_invoices';

    protected $fillable = [
        'company_quotation_id',
        'quote_id',
        'company_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'payment_terms',
        'payment_instructions',
        'currency',
        'company_details',
        'customer_details',
        'items',
        'subtotal',
        'discount',
        'tax_rate',
        'tax',
        'shipping',
        'balance_due',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'company_details' => 'array',
            'customer_details' => 'array',
            'items' => 'array',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    /**
     * Offer this invoice was issued for.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(CompanyQuotation::class, 'company_quotation_id');
    }

    public function getSubtotalLessDiscountAttribute(): float
    {
        return round((float) $this->subtotal - (float) $this->discount, 2);
    }
}
