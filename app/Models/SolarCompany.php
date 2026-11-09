<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolarCompany extends Authenticatable
{
    use Notifiable, SoftDeletes, LogsActivity;

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
        'on_app',
        'social_media',
        'license_number',
        'year_of_exp',
        'number_of_projects',
        'commission_rate',
        'show_email_in_app',
        'show_phone_in_app',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // Between SunXFinder and the company only; never in customer API output.
        'commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'on_app' => 'boolean',
            'can_notify_all_customers' => 'boolean',
            'all_customers_monthly_limit' => 'integer',
            'commission_rate' => 'decimal:2',
            'show_email_in_app' => 'boolean',
            'show_phone_in_app' => 'boolean',
        ];
    }

    /** Admin can hide the email or phone from customers in the app (both shown by default). */
    public function showsEmailInApp(): bool
    {
        return $this->show_email_in_app ?? true;
    }

    public function showsPhoneInApp(): bool
    {
        return $this->show_phone_in_app ?? true;
    }

    /** SunXFinder's commission on a price at this company's current rate. */
    public function commissionFor(float $price): float
    {
        return round($price * (float) $this->commission_rate / 100, 2);
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

    /** Sends this month that reached beyond the company's own customers. Deleting a send does not refund it. */
    public function allCustomerSendsThisMonth(): int
    {
        return Notification::withTrashed()
            ->where('sender_type', 'company')
            ->where('sender_id', $this->id)
            ->where('is_sponsored', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }
}
