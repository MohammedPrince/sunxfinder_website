<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class RatingValue extends Model
{
    use LogsActivity;

    protected $table = 'tbl_rating_values';

    /** The question answer scale is fixed at 1-4; admin only renames labels. */
    public const MIN = 1;
    public const MAX = 4;

    protected $fillable = [
        'value',
        'label_en',
        'label_ar',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    /** value => label in the given language, e.g. [1 => 'Poor', ...]. */
    public static function labels(string $lang = 'en'): array
    {
        $column = $lang === 'ar' ? 'label_ar' : 'label_en';

        return static::query()->orderBy('value')->pluck($column, 'value')->all();
    }
}
