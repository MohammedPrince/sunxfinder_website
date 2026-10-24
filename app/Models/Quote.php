<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\CompanyQuotation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Quote extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tbl_quote_requests';

    protected $fillable = [
        'customer_id',
        'company_id',
        'quote_name',
        'estimated_load',
        'peak_load',
        'daily_load',
        'appliances_ids',
        'message',
        'status',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'company_id');
    }

    public function companyQuotations(): HasMany
    {
        return $this->hasMany(CompanyQuotation::class, 'quote_id');
    }
    public function companyQuotationsWithStatus(): HasMany
    {
        return $this->hasMany(CompanyQuotation::class, 'quote_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'quote_id', 'id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class, 'quote_id', 'id');
    }

    /**
     * Appliance id => quantity from appliances_ids. Accepts what the app may send:
     * "1,2,5", "1,1,5" (repeat = quantity), "1:3,5:1" / "1x3" (id:qty),
     * "[1,2]" or '[{"id":1,"qty":3}]' / '{"1":3}'.
     *
     * @return array<int, int>
     */
    public function applianceQuantities(): array
    {
        $raw = trim((string) $this->appliances_ids);

        if ($raw === '') {
            return [];
        }

        $quantities = [];
        $add = function ($id, $qty = 1) use (&$quantities) {
            $id = (int) $id;
            $qty = max(1, (int) $qty);

            if ($id > 0) {
                $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
            }
        };

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            foreach ($decoded as $key => $item) {
                match (true) {
                    is_array($item) => $add($item['id'] ?? $item['appliance_id'] ?? 0, $item['qty'] ?? $item['quantity'] ?? $item['count'] ?? 1),
                    array_is_list($decoded) => $add($item),
                    default => $add($key, $item), // {"1": 3}
                };
            }

            return $quantities;
        }

        foreach (preg_split('/[\s,;|]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            [$id, $qty] = array_pad(preg_split('/[:x×*]/i', $part, 2), 2, 1);
            $add($id, $qty);
        }

        return $quantities;
    }

    /**
     * The devices with their watts, for the company and admin quote pages.
     * Uses each appliance's current watts; deleted appliances still show.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string, ar_name: ?string, group: ?string, watts: int, qty: int, total_watts: int}>
     */
    public function applianceItems(): \Illuminate\Support\Collection
    {
        $quantities = $this->applianceQuantities();

        if (empty($quantities)) {
            return collect();
        }

        $appliances = Appliance::withTrashed()
            // Include deleted groups too, so old quotes keep their group name.
            ->with(['group' => fn($query) => $query->withTrashed()->select('id', 'name')])
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy('id');

        return collect($quantities)->map(function (int $qty, int $id) use ($appliances) {
            $appliance = $appliances->get($id);
            $watts = (int) ($appliance?->watts ?? 0);

            return [
                'id' => $id,
                'name' => $appliance?->name ?? "Unknown device #{$id}",
                'ar_name' => $appliance?->ar_name,
                'group' => $appliance?->group?->name,
                'watts' => $watts,
                'qty' => $qty,
                'total_watts' => $watts * $qty,
            ];
        })->values();
    }

    /**
     * Where this request stands, in plain words, for list badges and the journey tracker.
     * Uses the loaded "project" relation and the offer count when available.
     *
     * @return array{key: string, label: string, class: string}
     */
    public function stage(?int $offersCount = null): array
    {
        $offersCount ??= $this->company_quotations_count
            ?? ($this->relationLoaded('companyQuotations') ? $this->companyQuotations->count() : 0);

        $project = $this->relationLoaded('project') ? $this->project : null;

        return match (true) {
            $this->status === 'cancelled' => ['key' => 'cancelled', 'label' => 'Cancelled by customer', 'class' => 'pill-purple'],
            $this->status === 'rejected' => ['key' => 'rejected', 'label' => 'Rejected', 'class' => 'pill-red'],
            $this->status === 'completed' || $project?->status === 'completed' => ['key' => 'completed', 'label' => 'Completed', 'class' => 'pill-green'],
            $project?->status === 'in_progress' => ['key' => 'installing', 'label' => 'Installation in progress', 'class' => 'pill-blue'],
            $this->status === 'accepted' || $project !== null => ['key' => 'accepted', 'label' => 'Offer accepted · not started', 'class' => 'pill-orange'],
            $offersCount > 0 => ['key' => 'comparing', 'label' => 'Customer comparing offers', 'class' => 'pill-yellow'],
            default => ['key' => 'waiting', 'label' => 'Waiting for offers', 'class' => 'pill-orange'],
        };
    }

    /**
     * Requests a company can see: assigned to it, open to all companies,
     * or requests it has already submitted an offer on.
     */
    public function scopeVisibleToCompany($query, int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->orWhereNull('company_id')
                ->orWhereHas('companyQuotations', function ($quotationQuery) use ($companyId) {
                    $quotationQuery->where('company_id', $companyId);
                });
        });
    }
}