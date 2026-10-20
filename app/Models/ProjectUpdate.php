<?php
// app/Models/ProjectUpdate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectUpdate extends Model
{
    use SoftDeletes;
    protected $table = 'tbl_project_updates';

    protected $fillable = [
        'project_id',
        'updated_by_type',
        'updated_by_id',
        'old_status',
        'new_status',
        'progress_percent',
        'message',
        'visible_to_customer',
    ];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'visible_to_customer' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
