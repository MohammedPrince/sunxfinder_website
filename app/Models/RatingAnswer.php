<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingAnswer extends Model
{
    protected $table = 'tbl_rating_answers';

    protected $fillable = [
        'rating_id',
        'question_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class, 'rating_id');
    }

    /** Includes deleted questions so old answers keep their title. */
    public function question(): BelongsTo
    {
        return $this->belongsTo(RatingQuestion::class, 'question_id')->withTrashed();
    }
}
