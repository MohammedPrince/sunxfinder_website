<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppSlider extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tbl_app_sliders';

    /** Cache key for the app's slider list; cleared whenever an admin changes a slide. */
    public const CACHE_KEY = 'app_sliders.visible';

    protected $fillable = [
        'name', 'description_ar', 'description_en', 'image_path', 'image_width', 'image_height',
        'link_url', 'interval_seconds', 'sort_order', 'is_active', 'starts_at', 'ends_at', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'interval_seconds' => 'integer',
        'image_width' => 'integer',
        'image_height' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function scopeCurrentlyVisible($query)
    {
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /** live, scheduled, expired or inactive — what the admin list shows. */
    public function displayState(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        return 'live';
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }
}
