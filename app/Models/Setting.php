<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
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
}
