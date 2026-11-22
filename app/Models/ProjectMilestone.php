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

    /**
     * The 5 default stages are saved in English (code checks the "Customer handover" title),
     * so they are translated when shown. Any other title is shown as written.
     */
    private const DEFAULT_STAGES = [
        'site inspection' => 'site_inspection',
        'equipment procurement' => 'equipment_procurement',
        'installation & wiring' => 'installation_wiring',
        'system testing' => 'system_testing',
        'customer handover' => 'customer_handover',
    ];

    /** Arabic for the customer app; the English stays in title / description. */
    protected $appends = ['ar_title', 'ar_description'];

    private function defaultStageKey(): ?string
    {
        return self::DEFAULT_STAGES[strtolower(trim((string) $this->title))] ?? null;
    }

    /** Title in the given (default: current) language. */
    public function displayTitle(?string $locale = null): string
    {
        $key = $this->defaultStageKey();

        return $key
            ? __("company/messages.default_milestones.$key.title", [], $locale)
            : (string) $this->title;
    }

    /** Description in the given language; translated only while it is still the default text. */
    public function displayDescription(?string $locale = null): ?string
    {
        $key = $this->defaultStageKey();
        $isDefault = $key
            && trim((string) $this->description) === __("company/messages.default_milestones.$key.description", [], 'en');

        return $isDefault
            ? __("company/messages.default_milestones.$key.description", [], $locale)
            : $this->description;
    }

    public function getArTitleAttribute(): string
    {
        return $this->displayTitle('ar');
    }

    public function getArDescriptionAttribute(): ?string
    {
        return $this->displayDescription('ar');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
