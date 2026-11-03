<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** App Home sections the admin can show/hide (Admin → Settings → App Home). */
class HomeSection extends Model
{
    use LogsActivity;

    protected $table = 'tbl_home_sections';

    /** Cache key for the app's section list; cleared whenever the admin saves a section. */
    public const CACHE_KEY = 'home_sections';

    /** Section key => admin label. Add a key here (and in the app) to control another section. */
    public const SECTIONS = [
        'featured_companies' => 'Featured Solar Companies',
    ];

    protected $fillable = ['key', 'is_visible', 'show_companies', 'title_en', 'title_ar', 'sort_order'];

    protected $casts = [
        'is_visible' => 'boolean',
        'show_companies' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function clearCache(): void
    {
        cache()->forget(self::CACHE_KEY);
    }

    /** Every known section, unsaved defaults included, so the admin page always has one card per section. */
    public static function allForAdmin()
    {
        $saved = self::query()->get()->keyBy('key');

        return collect(array_keys(self::SECTIONS))->map(fn(string $key) => $saved->get($key)
            ?? new self(['key' => $key, 'is_visible' => true, 'show_companies' => true]));
    }

    /** The only fields the app receives. */
    public function toApiArray(): array
    {
        return [
            'key' => $this->key,
            'is_visible' => (bool) $this->is_visible,
            'show_companies' => (bool) $this->show_companies,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
        ];
    }
}
