<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerComplaint extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_customer_complaints';

    protected $fillable = [
        'customer_id', 'company_id', 'category', 'description', 'status',
        'photos', 'admin_reply', 'customer_replied_at', 'company_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'customer_replied_at' => 'datetime',
            'company_notified_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }
}
