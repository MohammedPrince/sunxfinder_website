<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsActivity;

    protected $table = 'tbl_settings';

    /** Editable keys with their defaults. */
    public const DEFAULTS = [
        'max_open_quotes' => 2,
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, $default = null)
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** How many pending (no accepted offer) requests a customer may have. */
    public static function maxOpenQuotes(): int
    {
        return max(1, (int) static::get('max_open_quotes'));
    }

    /** Cache key for the maintenance settings (read on every public page). */
    public const MAINTENANCE_CACHE_KEY = 'site-maintenance';

    /**
     * Website maintenance mode: enabled flag, EN/AR title and message, expected end.
     * Cached for a minute; cleared whenever an admin saves the settings.
     */
    public static function maintenance(): array
    {
        return cache()->remember(self::MAINTENANCE_CACHE_KEY, 60, function () {
            $values = static::query()
                ->where('key', 'like', 'maintenance_%')
                ->pluck('value', 'key');

            return [
                'enabled' => (bool) ($values['maintenance_enabled'] ?? false),
                'title_en' => $values['maintenance_title_en'] ?? null,
                'title_ar' => $values['maintenance_title_ar'] ?? null,
                'message_en' => $values['maintenance_message_en'] ?? null,
                'message_ar' => $values['maintenance_message_ar'] ?? null,
                'until' => $values['maintenance_until'] ?? null,
            ];
        });
    }
}
