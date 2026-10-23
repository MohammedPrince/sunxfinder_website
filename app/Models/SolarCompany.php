<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolarCompany extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'tbl_solar_companies';

    protected $guard = 'company';

    /** Allowed values of tbl_solar_companies.status (enum). "deleted" is set when an admin deletes the company. */
    public const STATUSES = ['pending', 'approved', 'rejected', 'deleted'];

    /** Statuses an admin can pick in the company form. */
    public const EDITABLE_STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'name',
        'slug',
        'username',
        'email',
        'phone',
        'city',
        'license_number',
        'services',
        'ar_services',
        'description',
        'ar_description',
        'logo_path',
        'password',
        'status',
        'is_verified',
        'is_active',
        'social_media',
        'license_number',
        'year_of_exp',
        'number_of_projects'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'company_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'company_id');
    }

    /**
     * Average stars and count of visible ratings, e.g. ['rating' => 4.6, 'reviews' => 23].
     * Rating is null while the company has no ratings.
     */
    public function ratingSummary(): array
    {
        $row = Rating::query()
            ->visible()
            ->where('company_id', $this->id)
            ->selectRaw('AVG(stars) as avg_stars, COUNT(*) as total')
            ->first();

        $total = (int) ($row->total ?? 0);

        return [
            'rating' => $total > 0 ? round((float) $row->avg_stars, 1) : null,
            'reviews' => $total,
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function specs()
    {
        return $this->hasMany(ProductSpec::class, 'product_id');
    }

}
