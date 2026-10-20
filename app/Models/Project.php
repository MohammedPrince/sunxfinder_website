<?php
// app/Models/Project.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;
    protected $table = 'tbl_projects';


    protected $fillable = [
        'quote_id',
        'company_quotation_id',
        'customer_id',
        'company_id',
        'status',
        'progress_percent',
        'accepted_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public function companyQuotation(): BelongsTo
    {
        return $this->belongsTo(
            CompanyQuotation::class,
            'company_quotation_id'
        );
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id')
            ->orderBy('sort_order');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class, 'project_id')
            ->latest();
    }
}
