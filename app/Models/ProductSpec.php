<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ProductSpec extends Model
{
  
    protected $table = 'tbl_product_specs';
    protected $fillable = [
        'product_id',
        'spec_name',
        'spec_value',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
