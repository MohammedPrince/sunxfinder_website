<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplianceGroup extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tbl_appliance_groups';

    protected $fillable = [
        'name',
        'ar_name',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function appliances()
    {
        return $this->hasMany(Appliance::class, 'group_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}