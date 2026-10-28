<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchivedItem extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_archive';

    protected $fillable = [
        'customer_id',
        'type',
        'item_id',
        'data',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }
}
