<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appliance extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_appliances';

    protected $fillable = [
        'group_id',
        'name',
        'ar_name',
        'watts',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'watts' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function group()
    {
        return $this->belongsTo(ApplianceGroup::class, 'group_id');
    }
}