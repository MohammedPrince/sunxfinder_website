<?php
// app/Models/ProjectMilestone.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMilestone extends Model
{
    use SoftDeletes;
    protected $table = 'tbl_project_milestones';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'sort_order',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
