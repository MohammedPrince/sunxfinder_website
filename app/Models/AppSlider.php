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

    /** Same for the website home banner. */
    public const WEBSITE_CACHE_KEY = 'app_sliders.website';

    /**
     * Where the slide shows on the app Home screen. Only add a value here once the app
     * supports it: the app skips placements it does not know.
     */
    public const PLACEMENTS = [
        'home_top' => 'Home – top',
        'home_featured' => 'Home – Featured section',
        'home_bottom' => 'Home – bottom',
    ];

    /** Most slides the app receives for one placement. */
    public const MAX_PER_PLACEMENT = 10;

    protected $fillable = [
        'company_id', 'name', 'description_ar', 'description_en', 'image_path', 'image_width', 'image_height',
        'link_url', 'platform', 'placement', 'is_sponsored', 'interval_seconds', 'sort_order', 'is_active', 'starts_at', 'ends_at', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_sponsored' => 'boolean',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public static function clearCache(): void
    {
        cache()->forget(self::CACHE_KEY);
        cache()->forget(self::WEBSITE_CACHE_KEY);
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
