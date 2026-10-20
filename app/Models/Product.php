<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'inverter' => 'Inverter',
        'solar_panel' => 'Solar Panel',
        'battery' => 'Battery',
        'other' => 'Other',
    ];

    protected $table = 'tbl_products';

    protected $fillable = [
        'company_id',
        'supplier_id',
        'name',
        'model',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class, 'product_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type]
            ?? ucfirst(str_replace('_', ' ', $this->type ?? ''));
    }
}
