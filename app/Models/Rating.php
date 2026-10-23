<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rating extends Model
{
    protected $table = 'tbl_ratings';

    /** Overall stars the app sends (whole numbers only). */
    public const MIN_STARS = 1;
    public const MAX_STARS = 5;

    protected $fillable = [
        'customer_id',
        'company_id',
        'quote_id',
        'offer_id',
        'project_id',
        'stars',
        'comment',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RatingAnswer::class, 'rating_id');
    }

    /** Ratings shown publicly and counted in the company average. */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
