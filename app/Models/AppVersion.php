<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppVersion extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tbl_app_versions';

    public const PLATFORMS = [
        'android' => 'Android',
        'ios' => 'iOS',
    ];

    protected $fillable = [
        'platform',
        'minimum_version',
        'app_url',
        'force_update',
        'description_en',
        'description_ar',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'force_update' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
