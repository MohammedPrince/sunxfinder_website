<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RatingQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_rating_questions';

    protected $fillable = [
        'title_en',
        'title_ar',
        'question_en',
        'question_ar',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RatingAnswer::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
