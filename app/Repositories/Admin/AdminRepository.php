<?php

namespace App\Repositories\Admin;

use App\Jobs\SendCustomerAccountStatusEmail;
use App\Mail\CompanyJoinRequestMail;
use App\Mail\CompanyRequestWaitingMail;
use App\Models\ActivityLog;
use App\Models\ArchivedItem;
use App\Models\Admin;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Repositories\OtpRepository;
use App\Support\ActivityLogger;
use App\Support\ApkDownload;
use App\Support\DatabaseBackup;
use App\Models\Appliance;
use App\Models\ApplianceGroup;
use App\Models\AppVersion;
use App\Models\AppSlider;
use App\Models\CompanyCarousel;
use App\Models\CompanyQuotation;
use App\Models\ApiEndpoint;
use App\Models\Customer;
use App\Models\Project;
use App\Models\CustomerNotification;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\Quote;
use App\Models\Rating;
use App\Models\RatingQuestion;
use App\Models\RatingValue;
use App\Models\Setting;
use App\Models\SolarCompany;
use App\Models\SentEmail;
use App\Models\Supplier;
use App\Models\Visitor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Support\ReportPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CustomerDeviceToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use App\Helpers\Helper;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class AdminRepository
{
    protected OtpRepository $otpRepository;

    public function __construct(OtpRepository $otpRepository)
    {
        $this->otpRepository = $otpRepository;
    }

    //Password Recovery Start

    public function adminEmailExists(string $email): bool
    {
        return Admin::query()->where('email', $email)->exists();
    }

    public function sendPasswordResetOtp(string $email, string $locale = 'en'): array
    {
        $admin = Admin::query()->where('email', $email)->first();

        if (!$admin) {
            return ['success' => false, 'message' => 'No administrator account is registered with this email.'];
        }

        return $this->otpRepository->sendPasswordResetCode(
            'admin',
            $email,
            (string) $admin->username,
            'administrator',
            $locale
        );
    }

    public function resetPasswordWithOtp(string $email, string $code, string $password): array
    {
        $check = $this->otpRepository->verifyPasswordResetCode('admin', $email, $code);

        if (!$check['success']) {
            return $check;
        }

        $admin = Admin::query()->where('email', $email)->first();

        if (!$admin) {
            return ['success' => false, 'message' => 'No administrator account is registered with this email.'];
        }

        // The "hashed" cast hashes the new password; a new remember token signs out other devices.
        $admin->password = $password;
        $admin->setRememberToken(Str::random(60));
        $admin->save();

        $this->otpRepository->consume($check['otp']);

        return ['success' => true, 'message' => 'Your password has been reset. Sign in with your new password.'];
    }

    //Password Recovery End

    //Dashboard Start

    public function getDashboardData(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

        // Percentage change of records created this month against last month.
        $monthlyChange = function (string $modelClass) use ($thisMonth, $lastMonth): ?int {
            $current = $modelClass::query()->where('created_at', '>=', $thisMonth)->count();
            $previous = $modelClass::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return $previous > 0
                ? (int) round(($current - $previous) / $previous * 100)
                : null;
        };

        $companyCounts = SolarCompany::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $companies = [
            'total' => (int) $companyCounts->sum(),
            'approved' => (int) ($companyCounts['approved'] ?? 0),
            'pending' => (int) ($companyCounts['pending'] ?? 0),
            'new_this_month' => SolarCompany::query()->where('created_at', '>=', $thisMonth)->count(),
            'change' => $monthlyChange(SolarCompany::class),
        ];
        // Rejected and any other non-approved, non-pending status.
        $companies['other'] = $companies['total'] - $companies['approved'] - $companies['pending'];

        $quotes = [
            'total' => Quote::query()->count(),
            'pending' => Quote::query()->where('status', 'pending')->count(),
            'change' => $monthlyChange(Quote::class),
        ];

        $customers = [
            'total' => $this->customerAccountCount(),
            'active' => $this->activeCustomerAccountCount(),
            'change' => $monthlyChange(Customer::class),
        ];

        // Daily activity for the last 7 days, including today.
        $start = now()->subDays(6)->startOfDay();

        $perDay = fn(string $modelClass) => $modelClass::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $requestsPerDay = $perDay(Quote::class);
        $companiesPerDay = $perDay(SolarCompany::class);

        $activity = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();

            $activity[] = [
                'label' => $date->format('D'),
                'date' => $date->format('d M'),
                'requests' => (int) ($requestsPerDay[$key] ?? 0),
                'companies' => (int) ($companiesPerDay[$key] ?? 0),
            ];
        }

        $latestCompanies = SolarCompany::query()
            ->latest('created_at')
            ->latest('id')
            ->limit(5)
            ->get(['id', 'name', 'username', 'city', 'status', 'is_verified', 'is_active', 'created_at']);

        $topVisitorCountryRow = Visitor::query()
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as visitors')
            ->groupBy('country')
            ->orderByDesc('visitors')
            ->orderBy('country')
            ->first();

        $topVisitorCountry = null;
        if ($topVisitorCountryRow) {
            $countryCode = strtoupper($topVisitorCountryRow->country);
            $countryName = class_exists(\Locale::class)
                ? \Locale::getDisplayRegion('en_' . $countryCode, 'en')
                : '';

            $topVisitorCountry = [
                'name' => $countryName && $countryName !== $countryCode ? $countryName : $countryCode,
                'code' => $countryCode,
                'visitors' => (int) $topVisitorCountryRow->visitors,
            ];
        }

        return [
            'companies' => $companies,
            'quotes' => $quotes,
            'customers' => $customers,
            'activity' => $activity,
            'latestCompanies' => $latestCompanies,
            'topVisitorCountry' => $topVisitorCountry,
        ];
    }

    /**
     * Top bar: items that need an admin's action, plus today's activity.
     * Runs on every admin page, so it only does a few count queries.
     */
    public function getTopbarData(): array
    {
        $today = now()->startOfDay();

        // The top bar sorts by count; this order breaks ties.
        $attention = [
            [
                'label' => 'Customers waiting for approval',
                'hint' => 'New sign-ups who cannot log in to the app until approved.',
                'count' => Customer::query()->where('status', 'pending')->count(),
                'url' => route('admin.customers.index', ['status' => 'pending']),
                'tone' => 'orange',
            ],
            [
                'label' => 'Companies waiting for approval',
                'hint' => 'Review their details and approve or reject.',
                'count' => SolarCompany::query()->where('status', 'pending')->count(),
                'url' => route('admin.companies.index'),
                'tone' => 'orange',
            ],
            [
                'label' => 'Requests with no offers yet',
                'hint' => 'Customers waiting for a company to answer.',
                'count' => Quote::query()->where('status', 'pending')->doesntHave('companyQuotations')->count(),
                'url' => route('admin.quote-requests.index', ['status' => 'pending']),
                'tone' => 'blue',
            ],
            [
                'label' => 'Failed background jobs',
                'hint' => 'Emails or pushes that could not be sent. Open to see why and retry.',
                'count' => DB::table('failed_jobs')->count(),
                'url' => route('admin.queue.index'),
                'tone' => 'red',
            ],
            [
                'label' => 'Jobs stuck in the queue',
                'hint' => 'Waiting over ' . self::STUCK_JOB_MINUTES . ' minutes: the queue worker may not be running.',
                'count' => DB::table('jobs')->whereNull('reserved_at')
                    ->where('available_at', '<', now()->subMinutes(self::STUCK_JOB_MINUTES)->getTimestamp())->count(),
                'url' => route('admin.queue.index'),
                'tone' => 'red',
            ],
            [
                'label' => 'Projects idle for ' . ReportPeriod::STALLED_DAYS . '+ days',
                'hint' => 'No progress update from the company.',
                'count' => $this->idleProjectsQuery()->count(),
                'url' => route('admin.reports.index') . '#stalled-projects',
                'tone' => 'red',
            ],
        ];

        return [
            'attention' => $attention,
            'attentionTotal' => array_sum(array_column($attention, 'count')),
            'today' => [
                ['label' => 'New requests', 'count' => Quote::query()->where('created_at', '>=', $today)->count()],
                ['label' => 'New customers', 'count' => Customer::query()->where('created_at', '>=', $today)->count()],
                ['label' => 'New companies', 'count' => SolarCompany::query()->where('created_at', '>=', $today)->count()],
            ],
        ];
    }

    /** Open projects whose last update (or the project itself) is older than the stalled limit. */
    private function idleProjectsQuery()
    {
        $lastUpdate = DB::table('tbl_project_updates')
            ->whereNull('deleted_at')
            ->selectRaw('project_id, MAX(created_at) as last_update_at')
            ->groupBy('project_id');

        return DB::table('tbl_projects as p')
            ->leftJoinSub($lastUpdate, 'u', 'u.project_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.status', ['accepted', 'in_progress'])
            ->whereRaw('COALESCE(u.last_update_at, p.updated_at) < ?', [now()->subDays(ReportPeriod::STALLED_DAYS)]);
    }

    //Dashboard End

    //Reports Start

    public const REPORT_PRESETS = ReportPeriod::PRESETS;

    public const REPORT_STALLED_DAYS = ReportPeriod::STALLED_DAYS;

    /**
     * Everything on the Reports page for one date range.
     * "Period" figures use records created in the range; "current" figures are the state right now.
     */
    public function getReportsData(array $filters): array
    {
        $period = $this->resolveReportPeriod($filters);
        $from = $period['from'];
        $to = $period['to'];

        return [
            'period' => $period,
            'kpis' => $this->reportKpis($period),
            'trend' => $this->reportTrend($from, $to, $period['days']),
            'quotes' => $this->reportQuotes($from, $to),
            'offers' => $this->reportOffers($from, $to),
            'companyPerformance' => $this->reportCompanyPerformance($from, $to),
            'projects' => $this->reportProjects($from, $to),
            'customers' => $this->reportCustomers($from, $to),
            'companies' => $this->reportCompanies($from, $to),
            'catalog' => $this->reportCatalog(),
            'notifications' => $this->reportNotifications($from, $to),
        ];
    }

    /**
     * Rows for a CSV download of one report section.
     */
    public function getReportExport(string $section, array $filters): array
    {
        $period = $this->resolveReportPeriod($filters);
        $from = $period['from'];
        $to = $period['to'];
        $suffix = $from->format('Y-m-d') . '_to_' . $to->format('Y-m-d');

        if ($section === 'companies') {
            return [
                'filename' => "company-performance_{$suffix}.csv",
                'headers' => ['Company', 'City', 'Status', 'Verified', 'Offers', 'Selected', 'Not selected', 'Withdrawn', 'Win rate %', 'Avg offer (SDG)', 'Avg response (hours)', 'Customers', 'Active projects', 'Completed projects', 'Avg progress %', 'Products'],
                'rows' => array_map(fn($c) => [
                    $c['name'], $c['city'], $c['status'], $c['is_verified'] ? 'Yes' : 'No', $c['offers'], $c['selected'],
                    $c['not_selected'], $c['withdrawn'], $c['win_rate'] ?? '', $c['avg_price_sdg'] ?? '',
                    $c['avg_response_hours'] ?? '', $c['customers'], $c['active_projects'], $c['completed_projects'],
                    $c['avg_progress'] ?? '', $c['products'],
                ], $this->reportCompanyPerformance($from, $to)),
            ];
        }

        if ($section === 'quote_requests') {
            $offers = DB::table('tbl_company_quotations')
                ->whereNull('deleted_at')
                ->selectRaw('quote_id, COUNT(*) as offers')
                ->groupBy('quote_id');

            $rows = DB::table('tbl_quote_requests as q')
                ->leftJoin('tbl_customers as c', 'c.id', '=', 'q.customer_id')
                ->leftJoinSub($offers, 'o', 'o.quote_id', '=', 'q.id')
                ->leftJoin('tbl_company_quotations as s', function ($join) {
                    $join->on('s.quote_id', '=', 'q.id')->where('s.status', 'selected')->whereNull('s.deleted_at');
                })
                ->leftJoin('tbl_solar_companies as sc', 'sc.id', '=', 's.company_id')
                ->whereNull('q.deleted_at')
                ->whereBetween('q.created_at', [$from, $to])
                ->orderBy('q.created_at')
                ->get([
                    'q.id', 'q.created_at', 'c.full_name', 'c.username', 'c.city', 'q.quote_name', 'q.status',
                    'o.offers', 'sc.name as selected_company', 's.price as selected_price', 's.currency as selected_currency',
                ]);

            return [
                'filename' => "quote-requests_{$suffix}.csv",
                'headers' => ['Request', 'Received', 'Customer', 'City', 'Quote name', 'Status', 'Offers', 'Selected company', 'Selected price', 'Currency'],
                'rows' => $rows->map(fn($r) => [
                    '#Q-' . str_pad((string) $r->id, 5, '0', STR_PAD_LEFT), $r->created_at,
                    $r->full_name ?: $r->username, $r->city, $r->quote_name, $r->status, (int) $r->offers,
                    $r->selected_company, $r->selected_price, $r->selected_currency,
                ])->all(),
            ];
        }

        // New customers in the period (no contact details in exports).
        $requests = DB::table('tbl_quote_requests')
            ->whereNull('deleted_at')
            ->selectRaw('customer_id, COUNT(*) as requests')
            ->groupBy('customer_id');

        $rows = DB::table('tbl_customers as c')
            ->leftJoinSub($requests, 'r', 'r.customer_id', '=', 'c.id')
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$from, $to])
            ->orderBy('c.created_at')
            ->get(['c.id', 'c.full_name', 'c.username', 'c.city', 'c.gender', 'c.status', 'c.is_active', 'c.created_at', 'r.requests']);

        return [
            'filename' => "new-customers_{$suffix}.csv",
            'headers' => ['ID', 'Name', 'Username', 'City', 'Gender', 'Status', 'Active', 'Joined', 'Quote requests'],
            'rows' => $rows->map(fn($r) => [
                $r->id, $r->full_name, $r->username, $r->city, $r->gender, $r->status,
                $r->is_active ? 'Yes' : 'No', $r->created_at, (int) $r->requests,
            ])->all(),
        ];
    }

    private function resolveReportPeriod(array $filters): array
    {
        return ReportPeriod::resolve($filters, fn() => $this->reportFirstRecordDate());
    }

    private function reportFirstRecordDate(): Carbon
    {
        $dates = array_filter([
            DB::table('tbl_solar_companies')->min('created_at'),
            DB::table('tbl_customers')->min('created_at'),
            DB::table('tbl_quote_requests')->min('created_at'),
        ]);

        return $dates ? Carbon::parse(min($dates))->startOfDay() : now()->startOfYear();
    }

    private function reportCount(string $table, string $column, $from, $to, ?callable $scope = null): int
    {
        $query = DB::table($table)->whereNull('deleted_at')->whereBetween($column, [$from, $to]);

        if ($scope) {
            $scope($query);
        }

        return $query->count();
    }

    private function reportKpis(array $period): array
    {
        $definitions = [
            'quote_requests' => ['Quote requests', 'tbl_quote_requests', 'created_at', null],
            'offers' => ['Offers submitted', 'tbl_company_quotations', 'created_at', null],
            'offers_selected' => ['Offers selected', 'tbl_company_quotations', 'created_at', fn($q) => $q->where('status', 'selected')],
            'projects_started' => ['Projects started', 'tbl_projects', 'created_at', null],
            'projects_completed' => ['Projects completed', 'tbl_projects', 'completed_at', null],
            'new_customers' => ['New customers', 'tbl_customers', 'created_at', null],
            'new_companies' => ['New companies', 'tbl_solar_companies', 'created_at', null],
            'notifications_sent' => ['Notifications sent', 'tbl_notifications', 'published_at', fn($q) => $q->where('status', 'published')],
        ];

        $kpis = [];

        foreach ($definitions as $key => [$label, $table, $column, $scope]) {
            $current = $this->reportCount($table, $column, $period['from'], $period['to'], $scope);
            $previous = $period['previous_from']
                ? $this->reportCount($table, $column, $period['previous_from'], $period['previous_to'], $scope)
                : null;

            $kpis[$key] = [
                'label' => $label,
                'value' => $current,
                'previous' => $previous,
                'change' => ($previous !== null && $previous > 0)
                    ? (int) round(($current - $previous) / $previous * 100)
                    : null,
            ];
        }

        // Share of the period's requests that ended with an accepted offer.
        $requests = $kpis['quote_requests']['value'];
        $won = $this->reportCount('tbl_quote_requests', 'created_at', $period['from'], $period['to'],
            fn($q) => $q->whereIn('status', ['accepted', 'completed']));

        $kpis['conversion'] = [
            'label' => 'Request conversion',
            'value' => $requests > 0 ? (int) round($won / $requests * 100) : 0,
            'suffix' => '%',
            'previous' => null,
            'change' => null,
            'hint' => "{$won} of {$requests} requests accepted an offer",
        ];

        return $kpis;
    }

    private function reportTrend(Carbon $from, Carbon $to, int $days): array
    {
        // Daily buckets for up to ~2 months, monthly beyond that.
        $monthly = $days > 62;
        $format = $monthly ? '%Y-%m' : '%Y-%m-%d';

        $series = [
            'requests' => 'tbl_quote_requests',
            'offers' => 'tbl_company_quotations',
            'customers' => 'tbl_customers',
            'companies' => 'tbl_solar_companies',
        ];

        $counts = [];

        foreach ($series as $key => $table) {
            $counts[$key] = DB::table($table)
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw("DATE_FORMAT(created_at, '{$format}') as bucket, COUNT(*) as total")
                ->groupBy('bucket')
                ->pluck('total', 'bucket');
        }

        $buckets = [];
        $cursor = $monthly ? $from->copy()->startOfMonth() : $from->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($to)) {
            $key = $monthly ? $cursor->format('Y-m') : $cursor->format('Y-m-d');

            $buckets[] = [
                'label' => $monthly ? $cursor->format('M Y') : $cursor->format('d M'),
                'short' => $monthly ? $cursor->format('M') : $cursor->format('d'),
                'requests' => (int) ($counts['requests'][$key] ?? 0),
                'offers' => (int) ($counts['offers'][$key] ?? 0),
                'customers' => (int) ($counts['customers'][$key] ?? 0),
                'companies' => (int) ($counts['companies'][$key] ?? 0),
            ];

            $monthly ? $cursor->addMonth() : $cursor->addDay();
        }

        return [
            'monthly' => $monthly,
            'buckets' => $buckets,
            'max' => max(1, collect($buckets)->max(fn($b) => max($b['requests'], $b['offers']))),
        ];
    }

    private function reportQuotes(Carbon $from, Carbon $to): array
    {
        $base = fn() => DB::table('tbl_quote_requests as q')
            ->whereNull('q.deleted_at')
            ->whereBetween('q.created_at', [$from, $to]);

        $offerSummary = DB::table('tbl_company_quotations')
            ->whereNull('deleted_at')
            ->selectRaw('quote_id, COUNT(*) as offers, MIN(created_at) as first_offer_at')
            ->groupBy('quote_id');

        $coverage = $base()
            ->leftJoinSub($offerSummary, 'o', 'o.quote_id', '=', 'q.id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN o.offers > 0 THEN 1 ELSE 0 END) as with_offers')
            ->selectRaw('AVG(COALESCE(o.offers, 0)) as avg_offers')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, q.created_at, o.first_offer_at)) as avg_minutes_to_first_offer')
            ->first();

        $total = (int) $coverage->total;

        // Current backlog: pending requests that no company has answered yet (any date).
        $backlogQuery = DB::table('tbl_quote_requests as q')
            ->leftJoin('tbl_customers as c', 'c.id', '=', 'q.customer_id')
            ->whereNull('q.deleted_at')
            ->where('q.status', 'pending')
            ->whereNotExists(function ($sub) {
                $sub->from('tbl_company_quotations as o')
                    ->whereColumn('o.quote_id', 'q.id')
                    ->whereNull('o.deleted_at');
            });

        return [
            'total' => $total,
            'by_status' => $base()->selectRaw('q.status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'by_service' => $base()
                ->selectRaw("COALESCE(NULLIF(TRIM(q.quote_name), ''), 'Not specified') as k, COUNT(*) as n")
                ->groupBy('k')->orderByDesc('n')->limit(8)->pluck('n', 'k')->all(),
            'by_city' => $base()
                ->leftJoin('tbl_customers as c', 'c.id', '=', 'q.customer_id')
                ->selectRaw("COALESCE(NULLIF(TRIM(c.city), ''), 'Unknown') as k, COUNT(*) as n")
                ->groupBy('k')->orderByDesc('n')->limit(8)->pluck('n', 'k')->all(),
            'with_offers' => (int) $coverage->with_offers,
            'with_offers_rate' => $total > 0 ? (int) round($coverage->with_offers / $total * 100) : 0,
            'avg_offers' => round((float) $coverage->avg_offers, 1),
            'avg_hours_to_first_offer' => $coverage->avg_minutes_to_first_offer !== null
                ? round($coverage->avg_minutes_to_first_offer / 60, 1)
                : null,
            'backlog_count' => (clone $backlogQuery)->count(),
            'backlog' => $backlogQuery
                ->orderBy('q.created_at')
                ->limit(10)
                ->get(['q.id', 'q.created_at', 'q.quote_name', 'c.full_name', 'c.username', 'c.city'])
                ->map(fn($r) => [
                    'id' => $r->id,
                    'customer' => $r->full_name ?: ($r->username ?: '—'),
                    'city' => $r->city ?: '—',
                    'service' => $r->quote_name ?: '—',
                    'created_at' => Carbon::parse($r->created_at),
                    'age_hours' => (int) Carbon::parse($r->created_at)->diffInHours(now()),
                ])->all(),
        ];
    }

    private function reportOffers(Carbon $from, Carbon $to): array
    {
        $base = fn() => DB::table('tbl_company_quotations')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to]);

        $byStatus = $base()->selectRaw('status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all();

        $selected = (int) ($byStatus['selected'] ?? 0);
        $decided = $selected + (int) ($byStatus['not_selected'] ?? 0);

        return [
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'win_rate' => $decided > 0 ? (int) round($selected / $decided * 100) : null,
            'prices' => $base()
                ->selectRaw('currency, COUNT(*) as n, AVG(price) as avg_price, MIN(price) as min_price, MAX(price) as max_price')
                ->selectRaw("AVG(CASE WHEN status = 'selected' THEN price END) as avg_selected_price")
                ->selectRaw("SUM(CASE WHEN status = 'selected' THEN price ELSE 0 END) as selected_value")
                ->groupBy('currency')
                ->orderByDesc('n')
                ->get()
                ->map(fn($r) => [
                    'currency' => $r->currency,
                    'count' => (int) $r->n,
                    'avg' => (float) $r->avg_price,
                    'min' => (float) $r->min_price,
                    'max' => (float) $r->max_price,
                    'avg_selected' => $r->avg_selected_price !== null ? (float) $r->avg_selected_price : null,
                    'selected_value' => (float) $r->selected_value,
                ])->all(),
        ];
    }

    private function reportCompanyPerformance(Carbon $from, Carbon $to): array
    {
        $offers = DB::table('tbl_company_quotations as o')
            ->join('tbl_quote_requests as q', 'q.id', '=', 'o.quote_id')
            ->whereNull('o.deleted_at')
            ->whereBetween('o.created_at', [$from, $to])
            ->selectRaw('o.company_id, COUNT(*) as offers')
            ->selectRaw("SUM(o.status = 'selected') as selected")
            ->selectRaw("SUM(o.status = 'not_selected') as not_selected")
            ->selectRaw("SUM(o.status = 'withdrawn') as withdrawn")
            ->selectRaw("AVG(CASE WHEN o.currency = 'SDG' THEN o.price END) as avg_price_sdg")
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, q.created_at, o.created_at)) as avg_response_minutes')
            ->groupBy('o.company_id');

        $projects = DB::table('tbl_projects')
            ->whereNull('deleted_at')
            ->selectRaw('company_id')
            // A company's customers: everyone who accepted one of its offers (= has a project with it).
            ->selectRaw('COUNT(DISTINCT customer_id) as customers')
            ->selectRaw("SUM(status IN ('accepted', 'in_progress')) as active_projects")
            ->selectRaw("SUM(status = 'completed') as completed_projects")
            ->selectRaw("AVG(CASE WHEN status IN ('accepted', 'in_progress') THEN progress_percent END) as avg_progress")
            ->groupBy('company_id');

        $products = DB::table('tbl_products')
            ->whereNull('deleted_at')
            ->selectRaw('company_id, COUNT(*) as products')
            ->groupBy('company_id');

        return DB::table('tbl_solar_companies as sc')
            ->leftJoinSub($offers, 'o', 'o.company_id', '=', 'sc.id')
            ->leftJoinSub($projects, 'p', 'p.company_id', '=', 'sc.id')
            ->leftJoinSub($products, 'pr', 'pr.company_id', '=', 'sc.id')
            ->whereNull('sc.deleted_at')
            ->get([
                'sc.id', 'sc.name', 'sc.city', 'sc.status', 'sc.is_verified', 'sc.is_active',
                'o.offers', 'o.selected', 'o.not_selected', 'o.withdrawn', 'o.avg_price_sdg', 'o.avg_response_minutes',
                'p.customers', 'p.active_projects', 'p.completed_projects', 'p.avg_progress', 'pr.products',
            ])
            ->map(function ($r) {
                $selected = (int) $r->selected;
                $decided = $selected + (int) $r->not_selected;

                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'city' => $r->city ?: '—',
                    'status' => $r->status,
                    'is_verified' => (bool) $r->is_verified,
                    'is_active' => (bool) $r->is_active,
                    'offers' => (int) $r->offers,
                    'selected' => $selected,
                    'not_selected' => (int) $r->not_selected,
                    'withdrawn' => (int) $r->withdrawn,
                    'win_rate' => $decided > 0 ? (int) round($selected / $decided * 100) : null,
                    'avg_price_sdg' => $r->avg_price_sdg !== null ? round((float) $r->avg_price_sdg, 2) : null,
                    'avg_response_hours' => $r->avg_response_minutes !== null ? round($r->avg_response_minutes / 60, 1) : null,
                    'customers' => (int) $r->customers,
                    'active_projects' => (int) $r->active_projects,
                    'completed_projects' => (int) $r->completed_projects,
                    'avg_progress' => $r->avg_progress !== null ? (int) round($r->avg_progress) : null,
                    'products' => (int) $r->products,
                ];
            })
            ->sortBy([
                ['selected', 'desc'],
                ['offers', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function reportProjects(Carbon $from, Carbon $to): array
    {
        $current = DB::table('tbl_projects')->whereNull('deleted_at');

        $completedInPeriod = DB::table('tbl_projects')
            ->whereNull('deleted_at')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$from, $to]);

        $avgCompletionHours = (clone $completedInPeriod)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, COALESCE(accepted_at, created_at), completed_at)) as hours')
            ->value('hours');

        $lastUpdate = DB::table('tbl_project_updates')
            ->whereNull('deleted_at')
            ->selectRaw('project_id, MAX(created_at) as last_update_at')
            ->groupBy('project_id');

        $stalledBefore = now()->subDays(self::REPORT_STALLED_DAYS);

        $stalled = DB::table('tbl_projects as p')
            ->leftJoinSub($lastUpdate, 'u', 'u.project_id', '=', 'p.id')
            ->leftJoin('tbl_solar_companies as sc', 'sc.id', '=', 'p.company_id')
            ->leftJoin('tbl_customers as c', 'c.id', '=', 'p.customer_id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.status', ['accepted', 'in_progress'])
            ->whereRaw('COALESCE(u.last_update_at, p.updated_at) < ?', [$stalledBefore])
            ->orderByRaw('COALESCE(u.last_update_at, p.updated_at)')
            ->limit(10)
            ->get(['p.id', 'p.quote_id', 'p.status', 'p.progress_percent', 'sc.name as company', 'c.full_name', 'c.username', DB::raw('COALESCE(u.last_update_at, p.updated_at) as last_activity_at')])
            ->map(fn($r) => [
                'id' => $r->id,
                'quote_id' => $r->quote_id,
                'status' => $r->status,
                'progress' => (int) $r->progress_percent,
                'company' => $r->company ?: '—',
                'customer' => $r->full_name ?: ($r->username ?: '—'),
                'idle_days' => (int) Carbon::parse($r->last_activity_at)->diffInDays(now()),
            ])->all();

        return [
            'by_status' => (clone $current)->selectRaw('status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'total' => (clone $current)->count(),
            'started_in_period' => DB::table('tbl_projects')->whereNull('deleted_at')->whereBetween('created_at', [$from, $to])->count(),
            'completed_in_period' => (clone $completedInPeriod)->count(),
            'avg_completion_days' => $avgCompletionHours !== null ? round($avgCompletionHours / 24, 1) : null,
            'avg_active_progress' => (int) round((float) (clone $current)->whereIn('status', ['accepted', 'in_progress'])->avg('progress_percent')),
            'milestones' => DB::table('tbl_project_milestones as m')
                ->join('tbl_projects as p', 'p.id', '=', 'm.project_id')
                ->whereNull('m.deleted_at')
                ->whereNull('p.deleted_at')
                ->selectRaw('m.status as k, COUNT(*) as n')
                ->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'updates_in_period' => DB::table('tbl_project_updates')->whereNull('deleted_at')->whereBetween('created_at', [$from, $to])->count(),
            'stalled' => $stalled,
        ];
    }

    private function reportCustomers(Carbon $from, Carbon $to): array
    {
        $all = fn() => DB::table('tbl_customers')->whereNull('deleted_at');
        $total = $all()->count();

        $devices = DB::table('tbl_customer_device_tokens');
        $appUsers = (clone $devices)->distinct()->count('customer_id');

        $requestsInPeriod = DB::table('tbl_quote_requests')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("customer_id, COUNT(*) as requests, SUM(status IN ('accepted', 'completed')) as accepted")
            ->groupBy('customer_id');

        $customersWithRequests = (clone $requestsInPeriod)->get()->count();

        return [
            'total' => $total,
            'active' => $all()->where('is_active', true)->count(),
            'new_in_period' => $all()->whereBetween('created_at', [$from, $to])->count(),
            'by_status' => $all()->selectRaw('status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'by_city' => $all()->selectRaw("COALESCE(NULLIF(TRIM(city), ''), 'Unknown') as k, COUNT(*) as n")->groupBy('k')->orderByDesc('n')->limit(8)->pluck('n', 'k')->all(),
            'by_gender' => $all()->selectRaw("COALESCE(gender, 'Not set') as k, COUNT(*) as n")->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'without_email' => $all()->where(fn($q) => $q->whereNull('email')->orWhere('email', ''))->count(),
            'app_users' => $appUsers,
            'app_users_rate' => $total > 0 ? (int) round($appUsers / $total * 100) : 0,
            'by_platform' => (clone $devices)->selectRaw("COALESCE(platform, 'Unknown') as k, COUNT(*) as n")->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'by_lang' => (clone $devices)->selectRaw("COALESCE(lang, 'en') as k, COUNT(*) as n")->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'notifications_enabled' => (clone $devices)->where('is_active', true)->distinct()->count('customer_id'),
            'with_requests_in_period' => $customersWithRequests,
            'top_requesters' => DB::table('tbl_customers as c')
                ->joinSub($requestsInPeriod, 'r', 'r.customer_id', '=', 'c.id')
                ->whereNull('c.deleted_at')
                ->orderByDesc('r.requests')
                ->orderBy('c.full_name')
                ->limit(10)
                ->get(['c.id', 'c.full_name', 'c.username', 'c.city', 'r.requests', 'r.accepted'])
                ->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->full_name ?: $r->username,
                    'city' => $r->city ?: '—',
                    'requests' => (int) $r->requests,
                    'accepted' => (int) $r->accepted,
                ])->all(),
        ];
    }

    private function reportCompanies(Carbon $from, Carbon $to): array
    {
        $all = fn() => DB::table('tbl_solar_companies')->whereNull('deleted_at');

        $withProducts = DB::table('tbl_products')->whereNull('deleted_at')->distinct()->pluck('company_id');

        return [
            'total' => $all()->count(),
            'new_in_period' => $all()->whereBetween('created_at', [$from, $to])->count(),
            'by_status' => $all()->selectRaw('status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'verified' => $all()->where('is_verified', true)->count(),
            'active' => $all()->where('is_active', true)->count(),
            'by_city' => $all()->selectRaw("COALESCE(NULLIF(TRIM(city), ''), 'Unknown') as k, COUNT(*) as n")->groupBy('k')->orderByDesc('n')->limit(8)->pluck('n', 'k')->all(),
            'approved_without_products' => $all()->where('status', 'approved')->whereNotIn('id', $withProducts)->count(),
            'in_carousel' => DB::table('tbl_company_carousels')->whereNull('deleted_at')->where('is_active', true)->distinct()->count('company_id'),
        ];
    }

    private function reportCatalog(): array
    {
        $products = fn() => DB::table('tbl_products')->whereNull('tbl_products.deleted_at');

        return [
            'products' => $products()->count(),
            'active_products' => $products()->where('is_active', true)->count(),
            'by_type' => $products()->selectRaw('type as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'by_supplier' => $products()
                ->join('tbl_suppliers as s', 's.id', '=', 'tbl_products.supplier_id')
                ->whereNull('s.deleted_at')
                ->selectRaw('s.name as k, COUNT(*) as n')
                ->groupBy('k')->orderByDesc('n')->limit(8)->pluck('n', 'k')->all(),
            'suppliers' => DB::table('tbl_suppliers')->whereNull('deleted_at')->count(),
            'active_suppliers' => DB::table('tbl_suppliers')->whereNull('deleted_at')->where('is_active', true)->count(),
            'companies_with_products' => $products()->distinct()->count('company_id'),
        ];
    }

    private function reportNotifications(Carbon $from, Carbon $to): array
    {
        $sent = fn() => DB::table('tbl_notifications')
            ->whereNull('deleted_at')
            ->where('status', 'published')
            ->whereBetween('published_at', [$from, $to]);

        $deliveries = DB::table('tbl_customer_notifications as cn')
            ->joinSub($sent()->select('id'), 'n', 'n.id', '=', 'cn.notification_id')
            ->whereNull('cn.deleted_at')
            ->selectRaw('COUNT(*) as recipients, SUM(cn.read_at IS NOT NULL) as read_count')
            ->first();

        $recipients = (int) $deliveries->recipients;

        return [
            'sent' => $sent()->count(),
            'by_sender' => $sent()->selectRaw('sender_type as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'by_type' => $sent()->selectRaw('type as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'recipients' => $recipients,
            'read' => (int) $deliveries->read_count,
            'read_rate' => $recipients > 0 ? (int) round($deliveries->read_count / $recipients * 100) : null,
        ];
    }

    //Reports End

    //Compaines Managment Start

    public function getCompanies()
    {
        return SolarCompany::query()
            ->withAvg(['ratings as visible_rating_avg' => fn($query) => $query->visible()], 'stars')
            ->withCount(['ratings as visible_rating_count' => fn($query) => $query->visible()])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(10);
    }

    public function verifiedCount()
    {
        return SolarCompany::where('is_verified', true)->count();
    }

    public function pendingCount(): int
    {
        return SolarCompany::where('status', 'pending')->count();
    }

    public function inactiveCount()
    {
        return SolarCompany::where('is_active', false)->count();
    }

    public function addNewCompany(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $baseSlug = Str::slug($data['name']);

                if ($baseSlug === '') {
                    $baseSlug = 'solar-company';
                }

                // Generate a unique slug using the model
                $slug = $baseSlug;
                $counter = 1;

                while (SolarCompany::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                // Create the company using Eloquent
                SolarCompany::create([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'username' => $data['username'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'city' => $data['city'] ?? null,
                    'license_number' => $data['license_number'] ?? null,
                    'services' => $data['services'],
                    'ar_services' => $data['ar_services'],
                    'description' => $data['description'] ?? null,
                    'ar_description' => $data['ar_description'] ?? null,
                    'password' => Hash::make($data['password']),
                    'status' => $data['status'],
                    'is_verified' => (int) $data['is_verified'],
                    'is_active' => (int) $data['is_active'],
                    'year_of_exp' => (int) $data['year_of_exp'],
                    'number_of_projects' => (int) $data['number_of_projects'],

                ]);

                return [
                    'success' => true,
                    'message' => 'Company created successfully.',
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to create the company. Please check the submitted information and try again.',
            ];
        }
    }
    public function getCompanyById(int $id): array
    {
        $company = SolarCompany::find($id);

        if (!$company) {
            return [
                'success' => false,
                'message' => 'Company not found.',
            ];
        }

        return [
            'success' => true,
            'company' => $company,
        ];
    }

    public function updateCompany(int $id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $company = SolarCompany::find($id);

                if (!$company) {
                    return [
                        'success' => false,
                        'message' => 'Company not found.',
                    ];
                }

                // Validate unique fields in the repository.
                $validator = Validator::make($data, [
                    'username' => [
                        'required',
                        'string',
                        'max:191',
                        Rule::unique('tbl_solar_companies', 'username')
                            ->ignore($company->id),
                    ],
                    'email' => [
                        'nullable',
                        'email',
                        'max:191',
                        Rule::unique('tbl_solar_companies', 'email')
                            ->ignore($company->id),
                    ],
                ]);

                if ($validator->fails()) {
                    return [
                        'success' => false,
                        'message' => $validator->errors()->first(),
                    ];
                }

                $company->name = $data['name'];
                $company->username = $data['username'];
                $company->email = $data['email'] ?? null;
                $company->phone = $data['phone'] ?? null;
                $company->city = $data['city'] ?? null;
                $company->license_number = $data['license_number'] ?? null;
                $company->services = $data['services'] ?? null;
                $company->ar_services = $data['ar_services'] ?? null;
                $company->description = $data['description'] ?? null;
                $company->ar_description = $data['ar_description'] ?? null;
                $company->status = $data['status'];
                $company->is_verified = (int) $data['is_verified'];
                $company->is_active = (int) $data['is_active'];
                $company->year_of_exp = $data['year_of_exp'] ?? null;
                $company->number_of_projects = $data['number_of_projects'] ?? null;

                // Only change the password when a new one is supplied.
                if (!empty($data['password'])) {
                    $company->password = Hash::make($data['password']);
                }

                $company->save();

                return [
                    'success' => true,
                    'message' => 'Company updated successfully.',
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to update the company. Please try again.',
            ];
        }
    }
    public function deleteCompany(int $id): array
    {
        try {
            $company = SolarCompany::find($id);

            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found.',
                ];
            }

            app(Helper::class)->archiveItem($company, 'company', null, 'website');

            // Keep the status in step with the soft delete.
            $company->forceFill(['status' => 'deleted'])->save();
            $company->delete();

            return [
                'success' => true,
                'message' => 'Company deleted successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to delete the company. Please try again.',
            ];
        }
    }

    //Compaines Managment END

    //Customer Managment START

    public function createCustomer(array $data): array
    {
        try {
            $customer = new Customer();

            $customer->username = $data['username'];
            $customer->full_name = $data['full_name'];
            $customer->phone = $data['phone'] ?? null;
            // Optional: store NULL (not "") so the unique email index allows many blanks.
            $customer->email = ($data['email'] ?? null) ?: null;
            $customer->gender = $data['gender'];
            $customer->city = $data['city'] ?? null;
            $customer->address = $data['address'] ?? null;
            $customer->status = $data['status'];
            $customer->registration_source = 'admin';
            $customer->preferred_language = 'en';

            // Only active customers can have active accounts.
            $customer->is_active = $data['status'] === 'active' ? 1 : 0;

            // The Customer model's hashed cast hashes this password.
            $customer->password = $data['password'];

            if (!$customer->save()) {
                return [
                    'success' => false,
                    'message' => 'Unable to create the customer.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => $customer,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to create the customer. Please try again.',
            ];
        }
    }

    public function getCustomers(int $perPage = 10, ?string $status = null)
    {
        // The last company the customer dealt with = the company of their latest project
        // (a project is created when the customer accepts that company's offer).
        $lastProject = fn(string $column) => DB::table('tbl_projects as lp')
            ->join('tbl_solar_companies as lc', 'lc.id', '=', 'lp.company_id')
            ->whereColumn('lp.customer_id', 'tbl_customers.id')
            ->whereNull('lp.deleted_at')
            ->orderByDesc('lp.created_at')
            ->orderByDesc('lp.id')
            ->limit(1)
            ->select($column);

        return Customer::query()
            ->select('tbl_customers.*')
            ->selectSub($lastProject('lc.id'), 'last_company_id')
            ->selectSub($lastProject('lc.name'), 'last_company_name')
            ->selectSub($lastProject('lp.created_at'), 'last_company_at')
            // tokens_count > 0 means the customer is signed in on the app.
            ->withCount('tokens')
            // e.g. ?status=pending from the "Customers waiting for approval" alert.
            ->when($status, fn($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Customers with at least one valid app token (signed in on a device). */
    public function signedInCustomerCount(): int
    {
        return Customer::query()->has('tokens')->count();
    }

    /**
     * Signs a customer out of the app: revokes the API tokens (the next request gets 401)
     * and stops push notifications to their devices until they sign in again.
     */
    public function signOutCustomer(int $id): array
    {
        $customer = Customer::query()->find($id);

        if (!$customer) {
            return ['success' => false, 'message' => 'Customer not found.'];
        }

        $revoked = $this->revokeCustomerSessions($customer->id);
        $name = $customer->full_name ?: $customer->username;

        return [
            'success' => true,
            'message' => $revoked
                ? "{$name} was signed out of the app."
                : "{$name} was not signed in on any device.",
        ];
    }

    /** Signs every customer out of the app. They must log in again. */
    public function forceLogoutAllCustomers(): array
    {
        try {
            $customers = $this->revokeAllCustomerSessions();
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to sign customers out. Please try again.'];
        }

        return [
            'success' => true,
            'message' => $customers
                ? "{$customers} customer" . ($customers === 1 ? ' was' : 's were') . ' signed out of the app.'
                : 'No customers were signed in.',
        ];
    }

    /** Deletes every customer API token and turns off all device push tokens. Returns customers signed out. */
    private function revokeAllCustomerSessions(): int
    {
        $customers = Customer::query()->has('tokens')->count();

        DB::table('personal_access_tokens')
            ->where('tokenable_type', Customer::class)
            ->delete();

        CustomerDeviceToken::query()->update(['is_active' => false]);

        return $customers;
    }

    /** Deletes the customer's API tokens and turns off their device push tokens. Returns tokens removed. */
    private function revokeCustomerSessions(int $customerId): int
    {
        $revoked = DB::table('personal_access_tokens')
            ->where('tokenable_type', Customer::class)
            ->where('tokenable_id', $customerId)
            ->delete();

        CustomerDeviceToken::query()->where('customer_id', $customerId)->update(['is_active' => false]);

        return $revoked;
    }

    public function customerAccountCount(): int
    {
        return Customer::count();
    }

    public function activeCustomerAccountCount(): int
    {
        return Customer::where('status', 'active')
            ->where('is_active', true)
            ->count();
    }

    public function pendingCustomerAccountCount(): int
    {
        return Customer::where('status', 'pending')->count();
    }

    public function inactiveCustomerAccountCount(): int
    {
        return Customer::where('is_active', false)->count();
    }

    public function getCustomerById(int $id): array
    {
        try {
            $customer = Customer::query()
                // For the "App access" panel on the edit page.
                ->withCount([
                    'tokens',
                    'deviceTokens as active_devices_count' => fn($q) => $q->where('is_active', true),
                ])
                ->find($id);

            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found.',
                ];
            }

            return [
                'success' => true,
                'customer' => $customer,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to retrieve the customer.',
            ];
        }
    }

    public function updateCustomer(int $id, array $data): array
    {

        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found.',
                ];
            }

            $previousStatus = $customer->status;
            $wasActive = $previousStatus === 'active';

            $customer->username = $data['username'];
            $customer->full_name = $data['full_name'];
            $customer->phone = $data['phone'] ?? null;
            // Optional: store NULL (not "") so the unique email index allows many blanks.
            $customer->email = ($data['email'] ?? null) ?: null;
            $customer->gender = $data['gender'];
            $customer->city = $data['city'] ?? null;
            $customer->address = $data['address'] ?? null;
            $customer->status = $data['status'];

            // Set is_active automatically based on customer status.
            $customer->is_active = ($data['status'] === 'active') ? 1 : 0;

            // Update password only when a new password is provided.
            if (!empty($data['password'])) {
                $customer->password = $data['password'];
            }

            if (!$customer->save()) {
                return [
                    'success' => false,
                    'message' => 'Unable to update the customer.',
                ];
            }

            if ($customer->status === 'suspended') {
                // Revoke every app login immediately and stop push delivery.
                $customer->tokens()->delete();
                CustomerDeviceToken::query()
                    ->where('customer_id', $customer->id)
                    ->update(['is_active' => false]);
            }

            // Account approved (pending/suspended -> active): tell the customer.
            if (!$wasActive && $customer->status === 'active') {
                app(Helper::class)->notifyCustomerAccountApproved((int) $customer->id);
            }

            if (
                $previousStatus === 'pending'
                && $customer->registration_source === 'website'
                && in_array($customer->status, ['active', 'suspended'], true)
                && filter_var($customer->email, FILTER_VALIDATE_EMAIL)
            ) {
                try {
                    SendCustomerAccountStatusEmail::dispatch(
                        (int) $customer->id,
                        $customer->status === 'active' ? 'approved' : 'declined'
                    );
                } catch (Throwable $emailQueueException) {
                    // The status update is already saved; a mail queue problem must not undo it.
                    report($emailQueueException);
                }
            }

            return [
                'success' => true,
                'message' => 'Customer updated successfully.',
                'customer' => $customer,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to update the customer. Please try again.',
            ];
        }
    }

    /**
     * Stop or re-allow the mobile app for one customer. The app reads app_status and
     * the message (in its language) from /main-data. Optionally pushes the message.
     */
    public function setCustomerAppStatus(int $id, string $status, ?string $messageEn, ?string $messageAr, bool $push): array
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return ['success' => false, 'message' => 'Customer not found.'];
            }

            $stopping = $status === 'stopped';

            $customer->forceFill([
                'app_status' => $status,
                // Keep the message only while stopped; a reactivation note is pushed, not stored.
                'app_status_message_en' => $stopping ? $messageEn : null,
                'app_status_message_ar' => $stopping ? $messageAr : null,
                'app_status_changed_at' => now(),
            ])->save();

            $pushed = false;

            if ($push && ($messageEn || $messageAr)) {
                $this->publishNotification([
                    'sender_type' => 'admin',
                    'sender_id' => auth('admin')->id(),
                    'type' => 'app_status',
                    'audience_type' => 'selected',
                    'customer_ids' => [(int) $customer->id],
                    'title_en' => $stopping ? 'App access paused' : 'App access restored',
                    'title_ar' => $stopping ? 'تم إيقاف التطبيق مؤقتاً' : 'تمت إعادة تفعيل التطبيق',
                    'message_en' => $messageEn ?: $messageAr,
                    'message_ar' => $messageAr ?: $messageEn,
                ]);
                $pushed = true;
            }

            $name = $customer->full_name ?: $customer->username;

            return [
                'success' => true,
                'message' => ($stopping ? "App stopped for {$name}." : "App is active again for {$name}.")
                    . ($pushed ? ' The message was sent as a push notification.' : ''),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to change the app status. Please try again.'];
        }
    }

    public function deleteCustomer(int $id): array
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found.',
                ];
            }

            // Soft deletes the customer and all their data, and signs them out.
            $counts = app(Helper::class)->softDeleteCustomerData($customer);

            ActivityLogger::log('account_deleted', "Admin deleted customer \"{$customer->username}\" with all their data", $customer, $counts, null, 'customers');

            return [
                'success' => true,
                'message' => "Customer deleted with {$counts['quotes']} quote requests, "
                    . "{$counts['offers']} offers and {$counts['projects']} projects.",
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to delete the customer. Please try again.',
            ];
        }
    }

    //Customer Managment End


    // Appliance Groups and Items Start

    public function showApplianceGroups(): array
    {
        try {
            $groups = ApplianceGroup::with('appliances')
                ->withCount('appliances')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(10);

            return [
                'success' => true,
                'message' => 'Appliance groups retrieved successfully.',
                'groups' => $groups,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to retrieve appliance groups.',
                'groups' => null,
            ];
        }
    }

    public function createApplianceGroup(): array
    {
        $lastSortOrder = ApplianceGroup::max('sort_order') ?? 0;

        return [
            'success' => true,
            'message' => 'Appliance group form is ready.',
            'nextSortOrder' => $lastSortOrder + 1,
        ];
    }

    public function storeApplianceGroup(array $data): array
    {
        try {
            $group = DB::transaction(function () use ($data) {
                $group = new ApplianceGroup();

                $group->name = $data['name'];
                $group->ar_name = $data['ar_name'];
                $group->icon = $data['icon'] ?? null;
                $group->sort_order = $data['sort_order'] ?? 0;
                $group->is_active = (bool) $data['is_active'];

                $group->save();

                foreach ($data['appliances'] as $index => $item) {
                    $appliance = new Appliance();

                    $appliance->group_id = $group->id;
                    $appliance->name = $item['name'];
                    $appliance->ar_name = $item['ar_name'];
                    $appliance->watts = (int) $item['watts'];
                    $appliance->sort_order = $item['sort_order'] ?? $index;
                    $appliance->is_active = (bool) $item['is_active'];

                    $appliance->save();
                }

                return $group;
            });

            return [
                'success' => true,
                'message' => 'Appliance group and appliances created successfully.',
                'group' => $group,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to create the appliance group. Please try again.',
            ];
        }
    }
    public function editApplianceGroup(int $id): array
    {
        $group = ApplianceGroup::with('appliances')->find($id);

        if (!$group) {
            return [
                'success' => false,
                'message' => 'Appliance group not found.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Appliance group retrieved successfully.',
            'group' => $group,
        ];
    }

    public function updateApplianceGroup(int $id, array $data): array
    {
        try {
            $result = DB::transaction(function () use ($id, $data) {
                $group = ApplianceGroup::find($id);

                if (!$group) {
                    return [
                        'success' => false,
                        'message' => 'Appliance group not found.',
                    ];
                }

                $group->name = $data['name'];
                $group->ar_name = $data['ar_name'];
                $group->icon = $data['icon'] ?? null;
                $group->sort_order = $data['sort_order'] ?? 0;
                $group->is_active = (bool) $data['is_active'];
                $group->save();

                $submittedIds = [];

                foreach ($data['appliances'] as $index => $item) {
                    if (!empty($item['id'])) {
                        $appliance = Appliance::where('group_id', $group->id)
                            ->whereKey($item['id'])
                            ->first();

                        if (!$appliance) {
                            return [
                                'success' => false,
                                'message' => 'An appliance does not belong to this group or no longer exists.',
                            ];
                        }

                        $submittedIds[] = $appliance->id;
                    } else {
                        $appliance = new Appliance();
                        $appliance->group_id = $group->id;
                    }

                    $appliance->name = $item['name'];
                    $appliance->ar_name = $item['ar_name'];
                    $appliance->watts = (int) $item['watts'];
                    $appliance->sort_order = $item['sort_order'] ?? $index;
                    $appliance->is_active = (bool) $item['is_active'];
                    $appliance->save();

                    if (empty($item['id'])) {
                        $submittedIds[] = $appliance->id;
                    }
                }

                $removedAppliances = Appliance::where('group_id', $group->id);

                if (!empty($submittedIds)) {
                    $removedAppliances->whereNotIn('id', $submittedIds);
                }

                /** @var \Illuminate\Database\Eloquent\Collection<int, Appliance> $removedRows */
                $removedRows = $removedAppliances->get();

                $removedRows->each(function (Appliance $appliance) {
                    app(Helper::class)->archiveItem($appliance, 'appliance', null, 'website');
                    $appliance->delete();
                });


                return [
                    'success' => true,
                    'message' => 'Appliance group and appliances updated successfully.',
                ];
            });

            return $result;
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to update the appliance group. Please try again.',
            ];
        }
    }

    public function destroyApplianceGroup(int $id): array
    {
        try {
            return DB::transaction(function () use ($id) {
                $group = ApplianceGroup::find($id);

                if (!$group) {
                    return [
                        'success' => false,
                        'message' => 'Appliance group not found.',
                    ];
                }

                /** @var \Illuminate\Database\Eloquent\Collection<int, Appliance> $appliances */
                $appliances = Appliance::where('group_id', $group->id)->get();

                $appliances
                    ->each(function (Appliance $appliance) {
                        app(Helper::class)->archiveItem($appliance, 'appliance', null, 'website');
                        $appliance->delete();
                    });

                app(Helper::class)->archiveItem($group, 'appliance_group', null, 'website');
                $group->delete();

                return [
                    'success' => true,
                    'message' => 'Appliance group deleted successfully.',
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to delete the appliance group. Please try again.',
            ];
        }
    }

    //Appliance Groups and Items End

    //Supplier and Products Managment Start

    public function showSuppliers(): array
    {
        try {
            $suppliers = Supplier::withCount('products')->orderBy('name')->paginate(10);
            return ['success' => true, 'message' => 'Suppliers retrieved successfully.', 'suppliers' => $suppliers];
        } catch (Throwable $e) {
            Log::error('Unable to retrieve suppliers.', ['exception' => $e]);
            return ['success' => false, 'message' => 'Unable to retrieve suppliers.'];
        }
    }

    public function storeSupplier(array $data): array
    {
        try {
            $supplier = new Supplier();
            $supplier->name = trim($data['name']);
            $supplier->description = $data['description'] ?? null;
            $supplier->website = $data['website'] ?? null;
            $supplier->is_active = (bool) $data['is_active'];
            $supplier->save();
            return ['success' => true, 'message' => 'Supplier created successfully.', 'supplier' => $supplier];
        } catch (Throwable $e) {
            Log::error('Unable to create supplier.', ['exception' => $e]);
            return ['success' => false, 'message' => 'Unable to create supplier. Check whether the name already exists.'];
        }
    }

    public function editSupplier(int $id): array
    {
        $supplier = Supplier::find($id);
        return $supplier
            ? ['success' => true, 'message' => 'Supplier retrieved successfully.', 'supplier' => $supplier]
            : ['success' => false, 'message' => 'Supplier not found.'];
    }

    public function updateSupplier(int $id, array $data): array
    {
        try {
            $supplier = Supplier::find($id);
            if (!$supplier)
                return ['success' => false, 'message' => 'Supplier not found.'];
            $supplier->name = trim($data['name']);
            $supplier->description = $data['description'] ?? null;
            $supplier->website = $data['website'] ?? null;
            $supplier->is_active = (bool) $data['is_active'];
            $supplier->save();
            return ['success' => true, 'message' => 'Supplier updated successfully.', 'supplier' => $supplier];
        } catch (Throwable $e) {
            Log::error('Unable to update supplier.', ['supplier_id' => $id, 'exception' => $e]);
            return ['success' => false, 'message' => 'Unable to update supplier. Check whether the name already exists.'];
        }
    }

    public function destroySupplier(int $id): array
    {
        $supplier = Supplier::withCount('products')->find($id);
        if (!$supplier)
            return ['success' => false, 'message' => 'Supplier not found.'];
        if ($supplier->products_count > 0)
            return ['success' => false, 'message' => 'This supplier still has products. Delete or reassign its products first.'];
        app(Helper::class)->archiveItem($supplier, 'supplier', null, 'website');
        $supplier->delete();
        return ['success' => true, 'message' => 'Supplier deleted successfully.'];
    }

    public function showProducts(): array
    {
        try {
            $products = Product::with(['supplier', 'specs'])->orderBy('name')->paginate(10);
            $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
            return ['success' => true, 'message' => 'Products retrieved successfully.', 'products' => $products, 'suppliers' => $suppliers];
        } catch (Throwable $e) {
            Log::error('Unable to retrieve products.', ['exception' => $e]);
            return ['success' => false, 'message' => 'Unable to retrieve products.'];
        }
    }

    public function productFormData(): array
    {
        return ['success' => true, 'message' => 'Product form data retrieved successfully.', 'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get()];
    }

    public function storeProduct(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $product = new Product();
                $product->supplier_id = (int) $data['supplier_id'];
                $product->name = trim($data['name']);
                $product->model = isset($data['model']) ? trim($data['model']) : null;
                $product->type = $data['type'];
                $product->description = $data['description'] ?? null;
                $product->is_active = (bool) $data['is_active'];
                $product->save();
                $this->syncProductSpecs($product, $data['specs'] ?? []);
                return ['success' => true, 'message' => 'Product created successfully.', 'product' => $product];
            });
        } catch (Throwable $e) {
            Log::error('Unable to create product.', ['exception' => $e]);
            return ['success' => false, 'message' => 'Unable to create product.'];
        }
    }

    public function editProduct(int $id): array
    {
        $product = Product::with('specs')->find($id);
        if (!$product)
            return ['success' => false, 'message' => 'Product not found.'];
        return ['success' => true, 'message' => 'Product retrieved successfully.', 'product' => $product, 'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get()];
    }

    public function updateProduct(int $id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $product = Product::find($id);
                if (!$product)
                    return ['success' => false, 'message' => 'Product not found.'];
                $product->supplier_id = (int) $data['supplier_id'];
                $product->name = trim($data['name']);
                $product->model = isset($data['model']) ? trim($data['model']) : null;
                $product->type = $data['type'];
                $product->description = $data['description'] ?? null;
                $product->is_active = (bool) $data['is_active'];
                $product->save();
                $this->syncProductSpecs($product, $data['specs'] ?? []);
                return ['success' => true, 'message' => 'Product updated successfully.', 'product' => $product];
            });
        } catch (Throwable $e) {
            Log::error('Unable to update product.', ['product_id' => $id, 'exception' => $e]);
            return ['success' => false, 'message' => 'Unable to update product.'];
        }
    }

    public function destroyProduct(int $id): array
    {
        $product = Product::find($id);
        if (!$product)
            return ['success' => false, 'message' => 'Product not found.'];

        foreach ($product->specs as $spec) {
            app(Helper::class)->archiveItem($spec, 'product_spec', null, 'website');
        }
        app(Helper::class)->archiveItem($product, 'product', null, 'website');
        $product->delete();
        return ['success' => true, 'message' => 'Product deleted successfully.'];
    }

    private function syncProductSpecs(Product $product, array $specs): void
    {
        foreach ($product->specs as $spec) {
            app(Helper::class)->archiveItem($spec, 'product_spec', null, 'website');
        }
        $product->specs()->delete();
        $seen = [];
        foreach ($specs as $spec) {
            $name = trim((string) ($spec['spec_name'] ?? ''));
            $value = trim((string) ($spec['spec_value'] ?? ''));
            if ($name === '' && $value === '')
                continue;
            if ($name === '' || $value === '')
                throw new \InvalidArgumentException('Each specification needs both a name and a value.');
            $key = mb_strtolower($name);
            if (isset($seen[$key]))
                throw new \InvalidArgumentException('Specification names must be unique for each product.');
            $seen[$key] = true;
            $specModel = new ProductSpec();
            $specModel->product_id = $product->id;
            $specModel->spec_name = $name;
            $specModel->spec_value = $value;
            $specModel->save();
        }
    }


    //Supplier and Products Managment End

    //Company Carousel Start

    public function getCompanyCarouselEntries()
    {
        return CompanyCarousel::with('company')->orderBy('platform')->orderBy('sort_order')->orderByDesc('id')->paginate(10);
    }

    public function findCompanyCarouselEntry(int $id): ?CompanyCarousel
    {
        return CompanyCarousel::with('company')->find($id);
    }

    public function getCompaniesForCarouselSelect()
    {
        return SolarCompany::query()
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->where('is_verified', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getNextCompanyCarouselSortOrder(): int
    {
        return ((int) CompanyCarousel::max('sort_order')) + 1;
    }

    public function createCompanyCarouselEntry(array $data): array
    {
        try {
            // 1. Ensure the company exists, is approved, and is verified.
            $companyExists = SolarCompany::query()
                ->whereKey($data['company_id'])
                ->where('status', 'approved')
                ->where('is_verified', 1)
                ->exists();

            if (!$companyExists) {
                return [
                    'success' => false,
                    'message' => 'The selected company was not found or is not approved and verified.',
                ];
            }

            $newPlatform = $data['platform'];

            // 2. Get existing platform entries for this company.
            $existingPlatforms = CompanyCarousel::query()
                ->where('company_id', $data['company_id'])
                ->pluck('platform')
                ->toArray();

            // 3. Check platform conflicts.
            $hasConflict = match ($newPlatform) {
                // "both" is allowed only when the company has no existing entries.
                'both' => count($existingPlatforms) > 0,

                // "app" cannot coexist with another "app" or "both" entry.
                'app' => in_array('app', $existingPlatforms, true)
                    || in_array('both', $existingPlatforms, true),

                // "website" cannot coexist with another "website" or "both" entry.
                'website' => in_array('website', $existingPlatforms, true)
                    || in_array('both', $existingPlatforms, true),

                // Reject unsupported platform values.
                default => true,
            };

            if ($hasConflict) {
                $message = match ($newPlatform) {
                    'both' => 'This company already has a carousel entry. App and website cannot be selected.',
                    'app' => 'This company already has an entry for the mobile app.',
                    'website' => 'This company already has an entry for the website.',
                    default => 'The selected platform is invalid.',
                };

                return [
                    'success' => false,
                    'message' => $message,
                ];
            }

            // 4. Create the carousel entry.
            $entry = new CompanyCarousel();

            $this->assignCompanyCarouselFields($entry, $data);

            $entry->save();

            return [
                'success' => true,
                'message' => 'Carousel entry created successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to create the carousel entry. Please try again.',
            ];
        }
    }

    public function updateCompanyCarouselEntry(int $id, array $data): array
    {
        try {
            $entry = CompanyCarousel::find($id);
            if (!$entry)
                return ['success' => false, 'message' => 'Carousel entry not found.'];
            if (!SolarCompany::whereKey($data['company_id'])->exists()) {
                return ['success' => false, 'message' => 'The selected company was not found.'];
            }
            $this->assignCompanyCarouselFields($entry, $data);
            $entry->save();
            return ['success' => true, 'message' => 'Carousel entry updated successfully.'];
        } catch (Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Unable to update the carousel entry. Please try again.'];
        }
    }

    public function deleteCompanyCarouselEntry(int $id): array
    {
        try {
            $entry = CompanyCarousel::find($id);
            if (!$entry)
                return ['success' => false, 'message' => 'Carousel entry not found.'];
            app(Helper::class)->archiveItem($entry, 'company_carousel', null, 'website');
            $entry->delete();
            return ['success' => true, 'message' => 'Carousel entry deleted successfully.'];
        } catch (Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Unable to delete the carousel entry. Please try again.'];
        }
    }

    private function assignCompanyCarouselFields(CompanyCarousel $entry, array $data): void
    {
        $entry->company_id = (int) $data['company_id'];
        $entry->platform = $data['platform'];
        $entry->sort_order = (int) $data['sort_order'];
        $entry->interval_seconds = (int) $data['interval_seconds'];
        $entry->is_active = (bool) ($data['is_active'] ?? false);
        $entry->starts_at = $data['starts_at'] ?? null;
        $entry->ends_at = $data['ends_at'] ?? null;
    }

    //App Slider Start

    private const SLIDER_IMAGE_DIR = 'main/images/sliders';

    public function getAppSliders(array $filters = [])
    {
        $now = now();

        return AppSlider::query()
            ->with('creator:id,username')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $term = '%' . $filters['search'] . '%';
                $query->where(fn($q) => $q->where('name', 'like', $term)
                    ->orWhere('description_en', 'like', $term)
                    ->orWhere('description_ar', 'like', $term));
            })
            ->when(($filters['state'] ?? '') === 'live', fn($q) => $q->currentlyVisible())
            ->when(($filters['state'] ?? '') === 'scheduled', fn($q) => $q->where('is_active', true)->where('starts_at', '>', $now))
            ->when(($filters['state'] ?? '') === 'expired', fn($q) => $q->where('is_active', true)->where('ends_at', '<', $now))
            ->when(($filters['state'] ?? '') === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function getAppSliderMetrics(): array
    {
        return [
            'total' => AppSlider::count(),
            'live' => AppSlider::currentlyVisible()->count(),
            'scheduled' => AppSlider::where('is_active', true)->where('starts_at', '>', now())->count(),
            'inactive' => AppSlider::where('is_active', false)->count(),
        ];
    }

    public function findAppSlider(int $id): ?AppSlider
    {
        return AppSlider::find($id);
    }

    public function getNextAppSliderSortOrder(): int
    {
        return ((int) AppSlider::max('sort_order')) + 1;
    }

    public function createAppSlider(array $data, ?\Illuminate\Http\UploadedFile $image, ?int $adminId): array
    {
        try {
            if (!$image) {
                return ['success' => false, 'message' => 'Please upload a slide image.'];
            }

            $slider = new AppSlider();
            $this->assignAppSliderFields($slider, $data);
            $this->storeAppSliderImage($slider, $image);
            $slider->created_by = $adminId;
            $slider->save();
            Cache::forget(AppSlider::CACHE_KEY);

            return ['success' => true, 'message' => 'Slide created successfully.'];
        } catch (Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Unable to create the slide. Please try again.'];
        }
    }

    public function updateAppSlider(int $id, array $data, ?\Illuminate\Http\UploadedFile $image): array
    {
        try {
            $slider = AppSlider::find($id);
            if (!$slider) {
                return ['success' => false, 'message' => 'Slide not found.'];
            }

            $oldImage = $slider->image_path;
            $this->assignAppSliderFields($slider, $data);

            if ($image) {
                $this->storeAppSliderImage($slider, $image);
            }

            $slider->save();
            Cache::forget(AppSlider::CACHE_KEY);

            // Remove the replaced file only after the new one is saved.
            if ($image && $oldImage && $oldImage !== $slider->image_path) {
                $this->deleteAppSliderImageFile($oldImage);
            }

            return ['success' => true, 'message' => 'Slide updated successfully.'];
        } catch (Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Unable to update the slide. Please try again.'];
        }
    }

    public function toggleAppSlider(int $id): array
    {
        $slider = AppSlider::find($id);
        if (!$slider) {
            return ['success' => false, 'message' => 'Slide not found.'];
        }

        $slider->is_active = !$slider->is_active;
        $slider->save();
        Cache::forget(AppSlider::CACHE_KEY);

        return ['success' => true, 'message' => $slider->is_active ? 'Slide activated.' : 'Slide hidden from the app.'];
    }

    public function deleteAppSlider(int $id): array
    {
        try {
            $slider = AppSlider::find($id);
            if (!$slider) {
                return ['success' => false, 'message' => 'Slide not found.'];
            }

            // Soft delete: the image file stays so the slide can be restored from the archive.
            app(Helper::class)->archiveItem($slider, 'app_slider', null, 'website');
            $slider->delete();
            Cache::forget(AppSlider::CACHE_KEY);

            return ['success' => true, 'message' => 'Slide deleted successfully.'];
        } catch (Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Unable to delete the slide. Please try again.'];
        }
    }

    private function assignAppSliderFields(AppSlider $slider, array $data): void
    {
        $slider->name = trim($data['name']);
        $slider->description_ar = filled($data['description_ar'] ?? null) ? trim($data['description_ar']) : null;
        $slider->description_en = filled($data['description_en'] ?? null) ? trim($data['description_en']) : null;
        $slider->link_url = !empty($data['has_link']) && filled($data['link_url'] ?? null) ? trim($data['link_url']) : null;
        $slider->interval_seconds = (int) $data['interval_seconds'];
        $slider->sort_order = (int) $data['sort_order'];
        $slider->is_active = (bool) ($data['is_active'] ?? false);
        $slider->starts_at = $data['starts_at'] ?? null;
        $slider->ends_at = $data['ends_at'] ?? null;
    }

    private function storeAppSliderImage(AppSlider $slider, \Illuminate\Http\UploadedFile $image): void
    {
        $stored = Helper::storeOptimizedImage($image, self::SLIDER_IMAGE_DIR);
        $slider->image_path = $stored['path'];
        $slider->image_width = $stored['width'];
        $slider->image_height = $stored['height'];
    }

    private function deleteAppSliderImageFile(string $relativePath): void
    {
        // Only ever delete inside the slider folder.
        if (Str::startsWith($relativePath, self::SLIDER_IMAGE_DIR . '/')) {
            \Illuminate\Support\Facades\File::delete(public_path($relativePath));
        }
    }

    //App Slider End

    //App Versions Start

    public function getAppVersions()
    {
        return AppVersion::query()
            ->with('creator:id,username')
            ->orderBy('platform')
            ->get();
    }

    public function findAppVersion(int $id): ?AppVersion
    {
        return AppVersion::find($id);
    }

    public function createAppVersion(array $data): array
    {
        try {
            $version = new AppVersion();
            $this->assignAppVersionFields($version, $data);
            $version->created_by = auth('admin')->id();
            $version->save();

            return [
                'success' => true,
                'message' => AppVersion::PLATFORMS[$version->platform] . ' app version saved successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to save the app version. Please try again.',
            ];
        }
    }

    public function updateAppVersion(int $id, array $data): array
    {
        try {
            $version = AppVersion::find($id);

            if (!$version) {
                return ['success' => false, 'message' => 'App version not found.'];
            }

            $this->assignAppVersionFields($version, $data);
            $version->save();

            return [
                'success' => true,
                'message' => AppVersion::PLATFORMS[$version->platform] . ' app version updated successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to update the app version. Please try again.',
            ];
        }
    }

    public function deleteAppVersion(int $id): array
    {
        try {
            $version = AppVersion::find($id);

            if (!$version) {
                return ['success' => false, 'message' => 'App version not found.'];
            }

            app(Helper::class)->archiveItem($version, 'app_version', null, 'website');
            $version->delete();

            return ['success' => true, 'message' => 'App version deleted successfully.'];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to delete the app version. Please try again.',
            ];
        }
    }

    private function assignAppVersionFields(AppVersion $version, array $data): void
    {
        $version->platform = $data['platform'];
        $version->minimum_version = $data['minimum_version'];
        $version->app_url = $data['app_url'];
        $version->force_update = (bool) ($data['force_update'] ?? false);
        $version->description_en = $data['description_en'] ?? null;
        $version->description_ar = $data['description_ar'] ?? null;
    }

    //App Versions End

    //APK Direct Download Start

    public function storeApkChunk(string $uploadId, int $index, string $chunkPath): array
    {
        try {
            ApkDownload::storeChunk($uploadId, $index, $chunkPath);

            return ['success' => true, 'message' => 'Chunk saved.'];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Upload failed. Please try again.'];
        }
    }

    public function finishApkUpload(string $uploadId, int $total, ?string $version): array
    {
        try {
            ApkDownload::assemble($uploadId, $total);

            Setting::set('apk_version', $version);
            Setting::set('apk_uploaded_at', now()->toDateTimeString());
            ApkDownload::forgetCache();

            return ['success' => true, 'message' => 'APK uploaded successfully.'];
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to save the APK. Please try again.'];
        }
    }

    public function setApkDownloadEnabled(bool $enabled): array
    {
        if ($enabled && !is_file(ApkDownload::path())) {
            return ['success' => false, 'message' => 'Upload an APK before showing the download button.'];
        }

        Setting::set('apk_download_enabled', $enabled ? '1' : '0');
        ApkDownload::forgetCache();

        return [
            'success' => true,
            'message' => $enabled
                ? 'The Direct Download button is now shown on the home page.'
                : 'The Direct Download button is now hidden.',
        ];
    }

    public function deleteApk(): array
    {
        try {
            ApkDownload::delete();
            Setting::set('apk_download_enabled', '0');
            Setting::set('apk_version', null);
            Setting::set('apk_uploaded_at', null);
            ApkDownload::forgetCache();

            return ['success' => true, 'message' => 'APK deleted. The download button is hidden.'];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to delete the APK. Please try again.'];
        }
    }

    public function setPlayStoreButtonEnabled(bool $enabled): array
    {
        Setting::set('play_store_button_enabled', $enabled ? '1' : '0');
        cache()->forget(Setting::PLAY_STORE_BUTTON_CACHE_KEY);

        return [
            'success' => true,
            'message' => $enabled
                ? 'The Google Play button is now shown on the home page.'
                : 'The Google Play button is now hidden on the home page.',
        ];
    }

    //APK Direct Download End

    // Call from your existing public/mobile home-data repository/service.
    public function getCompanyCarouselForPlatform(string $platform)
    {
        abort_unless(in_array($platform, ['app', 'website'], true), 422);
        /** @var \Illuminate\Database\Eloquent\Collection<int, CompanyCarousel> $entries */
        $entries = CompanyCarousel::currentlyVisible()
            ->whereIn('platform', [$platform, 'both'])
            ->with('company')->orderBy('sort_order')->orderBy('id')->get();

        return $entries
            ->map(fn(CompanyCarousel $entry) => [
                'carousel_id' => $entry->id,
                'company_id' => $entry->company_id,
                'company' => $entry->company,
                'sort_order' => $entry->sort_order,
                'interval_seconds' => $entry->interval_seconds,
                'platform' => $entry->platform,
            ]);
    }

    //Company Carousel End

    //Quote Requests Start

    public function getQuoteRequests(array $filters = []): LengthAwarePaginator
    {
        $query = Quote::query()
            ->with([
                'customer',
                'company',
                'project:id,quote_id,company_id,status,progress_percent',
            ])
            ->withCount('companyQuotations')
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search)
                            ->orWhere('customer_id', (int) $search);
                    }

                    $q->orWhere('quote_name', 'like', "%{$search}%")
                        ->orWhere('estimated_load', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            // tbl_customers has full_name/username, not name.
                            $customerQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('company', function ($companyQuery) use ($search) {
                            $companyQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $query->paginate(10)->withQueryString();
    }

    public function getQuoteRequestMetrics(): array
    {
        $query = Quote::query();

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            // Pending requests no company has answered yet.
            'awaiting_offers' => (clone $query)->where('status', 'pending')->doesntHave('companyQuotations')->count(),
            'in_progress' => (clone $query)->where('status', 'accepted')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
        ];
    }

    public function getQuoteRequestById(int $id): ?Quote
    {
        return Quote::query()
            ->with([
                'customer',
                'company',
                // Every offer received, cheapest first, with the company that sent it.
                'companyQuotations' => fn($q) => $q->with('company:id,name,city,phone,is_verified')->orderBy('price'),
                'project.milestones',
                'project.updates',
            ])
            ->withCount('companyQuotations')
            ->find($id);
    }

    public function getAdminProjects(array $filters = []): LengthAwarePaginator
    {
        $query = $this->adminProjectsQuery($filters)
            ->with([
                'company' => fn($q) => $q->withTrashed(),
                'customer' => fn($q) => $q->withTrashed(),
                'quote',
            ])
            ->select('tbl_projects.*')
            ->selectSub(
                DB::table('tbl_project_updates')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('project_id', 'tbl_projects.id')
                    ->whereNull('deleted_at'),
                'last_update_at'
            )
            ->orderByRaw($this->projectLastActivitySql() . ' DESC')
            ->orderByDesc('tbl_projects.id');

        return $query->paginate(25)->withQueryString();
    }

    public function getAdminProjectById(int $id): ?Project
    {
        return Project::query()
            ->with([
                'company' => fn($q) => $q->withTrashed(),
                'customer' => fn($q) => $q->withTrashed(),
                'quote',
                'companyQuotation',
                'milestones',
                'updates',
            ])
            ->find($id);
    }

    public function getAdminProjectMetrics(): array
    {
        $query = Project::query();

        return [
            'total' => (clone $query)->count(),
            'accepted' => (clone $query)->where('status', 'accepted')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'stalled' => (clone $query)
                ->whereIn('status', ['accepted', 'in_progress'])
                ->whereRaw($this->projectLastActivitySql() . ' < ?', [now()->subDays(ReportPeriod::STALLED_DAYS)])
                ->count(),
        ];
    }

    public function getAdminProjectCompanies()
    {
        return SolarCompany::withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']);
    }

    private function adminProjectsQuery(array $filters)
    {
        return Project::query()
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['company_id']), fn($q) => $q->where('company_id', (int) $filters['company_id']))
            ->when(!empty($filters['customer']), function ($query) use ($filters) {
                $search = trim($filters['customer']);
                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('customer_id', (int) $search);
                    }

                    $q->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->withTrashed()
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                });
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('tbl_projects.id', (int) $search)
                            ->orWhere('quote_id', (int) $search);
                    }

                    $q->orWhereHas('quote', fn($quoteQuery) => $quoteQuery->where('quote_name', 'like', "%{$search}%"))
                        ->orWhereHas('company', fn($companyQuery) => $companyQuery->withTrashed()->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(!empty($filters['last_activity_from']), fn($q) => $q->whereRaw(
                $this->projectLastActivitySql() . ' >= ?',
                [Carbon::parse($filters['last_activity_from'])->startOfDay()]
            ))
            ->when(!empty($filters['last_activity_to']), fn($q) => $q->whereRaw(
                $this->projectLastActivitySql() . ' <= ?',
                [Carbon::parse($filters['last_activity_to'])->endOfDay()]
            ))
            ->when(!empty($filters['stalled']), fn($q) => $q
                ->whereIn('status', ['accepted', 'in_progress'])
                ->whereRaw($this->projectLastActivitySql() . ' < ?', [now()->subDays(ReportPeriod::STALLED_DAYS)]));
    }

    private function projectLastActivitySql(): string
    {
        return 'COALESCE((SELECT MAX(pu.created_at) FROM tbl_project_updates pu WHERE pu.project_id = tbl_projects.id AND pu.deleted_at IS NULL), tbl_projects.updated_at)';
    }

    /** Hours before the same request can be nudged again. */
    public const NUDGE_COOLDOWN_HOURS = 12;

    /** Companies that can receive requests: approved, active and verified. */
    public function getAssignableCompanies()
    {
        return SolarCompany::query()
            ->where('status', 'approved')
            ->where('is_active', true)
            ->where('is_verified', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city']);
    }

    /** When this request was last nudged (null if never or the cooldown has passed). */
    public function getQuoteNudgedAt(int $quoteId): ?Carbon
    {
        $at = Cache::get("quote-nudged:{$quoteId}");

        return $at ? Carbon::parse($at) : null;
    }

    /**
     * Close a spam or dead request. Only open (pending) requests can be cancelled;
     * waiting offers are withdrawn so companies stop seeing it as active.
     */
    public function cancelQuoteRequest(int $id, string $reason, bool $notifyCustomer): array
    {
        try {
            $quote = Quote::find($id);

            if (!$quote) {
                return ['success' => false, 'message' => 'Quote request not found.'];
            }

            if ($quote->status !== 'pending') {
                return ['success' => false, 'message' => 'Only open requests (no accepted offer yet) can be cancelled.'];
            }

            $withdrawn = DB::transaction(function () use ($quote) {
                $quote->status = 'cancelled';
                $quote->save();

                return CompanyQuotation::query()
                    ->where('quote_id', $quote->id)
                    ->where('status', 'submitted')
                    ->update(['status' => 'withdrawn', 'updated_at' => now()]);
            });

            $reference = '#Q-' . str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT);

            ActivityLogger::log('cancelled', "Admin cancelled quote request {$reference}: {$reason}", $quote, [
                'reason' => $reason,
                'offers_withdrawn' => $withdrawn,
                'customer_notified' => $notifyCustomer,
            ], null, 'quotes');

            if ($notifyCustomer && $quote->customer_id) {
                app(Helper::class)->notifyCustomer((int) $quote->customer_id, 'request_cancelled', $reference, 'admin');
            }

            return [
                'success' => true,
                'message' => "Request {$reference} cancelled." . ($withdrawn ? " {$withdrawn} waiting " . Str::plural('offer', $withdrawn) . ' withdrawn.' : '')
                    . ($notifyCustomer ? ' The customer was notified.' : ''),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to cancel the request. Please try again.'];
        }
    }

    /**
     * Send an open request to one company, or open it to all companies (null).
     * The chosen company gets an email that a customer is waiting.
     */
    public function reassignQuoteRequest(int $id, ?int $companyId): array
    {
        try {
            $quote = Quote::query()->with('project:id,quote_id')->find($id);

            if (!$quote) {
                return ['success' => false, 'message' => 'Quote request not found.'];
            }

            if ($quote->status !== 'pending' || $quote->project) {
                return ['success' => false, 'message' => 'Only open requests (no accepted offer yet) can be reassigned.'];
            }

            $company = null;

            if ($companyId) {
                $company = $this->getAssignableCompanies()->firstWhere('id', $companyId);

                if (!$company) {
                    return ['success' => false, 'message' => 'Choose an approved, active and verified company.'];
                }
            }

            if ((int) $quote->company_id === (int) $companyId) {
                return ['success' => false, 'message' => 'The request is already assigned that way.'];
            }

            $previous = $quote->company_id ? SolarCompany::query()->whereKey($quote->company_id)->value('name') : 'all companies';

            $quote->company_id = $companyId;
            $quote->save();

            $reference = '#Q-' . str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT);
            $target = $company?->name ?? 'all companies';

            ActivityLogger::log('reassigned', "Admin reassigned quote request {$reference} from {$previous} to {$target}", $quote, [
                'from' => $previous,
                'to' => $target,
            ], null, 'quotes');

            $emailed = $company ? $this->emailCompaniesAboutQuote($quote, collect([$company->id])) : 0;

            return [
                'success' => true,
                'message' => "Request {$reference} is now " . ($company ? "sent to {$company->name}" : 'open to all companies') . '.'
                    . ($emailed ? " {$company->name} was emailed." : ''),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to reassign the request. Please try again.'];
        }
    }

    /**
     * Email companies that have not sent an offer yet: the assigned company, or every
     * approved company when the request is open to all. Once per NUDGE_COOLDOWN_HOURS.
     */
    public function nudgeCompaniesForQuote(int $id): array
    {
        try {
            $quote = Quote::find($id);

            if (!$quote) {
                return ['success' => false, 'message' => 'Quote request not found.'];
            }

            if ($quote->status !== 'pending') {
                return ['success' => false, 'message' => 'Only open requests can be nudged.'];
            }

            if ($nudgedAt = $this->getQuoteNudgedAt($quote->id)) {
                return [
                    'success' => false,
                    'message' => 'Companies were already reminded ' . $nudgedAt->diffForHumans() . '. You can nudge again after '
                        . $nudgedAt->copy()->addHours(self::NUDGE_COOLDOWN_HOURS)->format('M d, H:i') . '.',
                ];
            }

            $alreadyOffered = CompanyQuotation::query()->where('quote_id', $quote->id)->pluck('company_id');

            $companyIds = $this->getAssignableCompanies()
                ->when($quote->company_id, fn($companies) => $companies->where('id', $quote->company_id))
                ->whereNotIn('id', $alreadyOffered)
                ->pluck('id');

            if ($companyIds->isEmpty()) {
                return ['success' => false, 'message' => 'No company to remind: every eligible company has already sent an offer.'];
            }

            $emailed = $this->emailCompaniesAboutQuote($quote, $companyIds);

            Cache::put("quote-nudged:{$quote->id}", now()->toIso8601String(), now()->addHours(self::NUDGE_COOLDOWN_HOURS));

            $reference = '#Q-' . str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT);

            ActivityLogger::log('nudged', "Admin reminded {$emailed} " . Str::plural('company', $emailed) . " about quote request {$reference}", $quote, [
                'companies_emailed' => $emailed,
                'company_ids' => $companyIds->values()->all(),
            ], null, 'quotes');

            return [
                'success' => true,
                'message' => "Reminder emailed to {$emailed} " . Str::plural('company', $emailed) . '.',
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to send the reminders. Please try again.'];
        }
    }

    /** Queues a "customer is waiting for your offer" email per company with an email address. */
    private function emailCompaniesAboutQuote(Quote $quote, $companyIds): int
    {
        $customerCity = $quote->customer_id ? Customer::query()->whereKey($quote->customer_id)->value('city') : null;
        $reference = '#Q-' . str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT);
        $sent = 0;

        $companies = SolarCompany::query()
            ->whereIn('id', $companyIds)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'name', 'email', 'phone']);

        foreach ($companies as $company) {
            Mail::mailer('offer')->to($company->email)->queue(new CompanyRequestWaitingMail([
                'companyName' => $company->name,
                'reference' => $reference,
                'quoteName' => $quote->quote_name,
                'city' => $customerCity,
                'dailyLoad' => $quote->daily_load,
                'peakLoad' => $quote->peak_load,
                'actionUrl' => route('company.quote-requests.show', $quote->id),
            ]));

            $sent++;
        }

        return $sent;
    }

    //Quote Requests End

    //Background Jobs Start

    /** Pending jobs this old mean the queue worker is probably not running. */
    public const STUCK_JOB_MINUTES = 10;

    public function getQueueOverview(): array
    {
        $oldestPending = DB::table('jobs')->whereNull('reserved_at')->min('available_at');

        return [
            'pending' => DB::table('jobs')->count(),
            'running' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
            'oldest_pending_at' => $oldestPending ? Carbon::createFromTimestamp($oldestPending) : null,
            'stuck' => DB::table('jobs')
                ->whereNull('reserved_at')
                ->where('available_at', '<', now()->subMinutes(self::STUCK_JOB_MINUTES)->getTimestamp())
                ->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'failed_today' => DB::table('failed_jobs')->where('failed_at', '>=', now()->startOfDay())->count(),
            'connection' => config('queue.default'),
        ];
    }

    public function getFailedJobs(array $filters = []): LengthAwarePaginator
    {
        $paginator = DB::table('failed_jobs')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(fn($q) => $q->where('payload', 'like', "%{$search}%")->orWhere('exception', 'like', "%{$search}%"));
            })
            ->orderByDesc('failed_at')
            ->paginate(20)
            ->withQueryString();

        $paginator->getCollection()->transform(fn($job) => $this->describeFailedJob($job));

        return $paginator;
    }

    public function getFailedJob(string $uuid): ?object
    {
        $job = DB::table('failed_jobs')->where('uuid', $uuid)->first();

        return $job ? $this->describeFailedJob($job, true) : null;
    }

    public function retryFailedJobs(array|string $uuids): array
    {
        try {
            $ids = $uuids === 'all' ? ['all'] : (array) $uuids;
            $count = $uuids === 'all' ? DB::table('failed_jobs')->count() : DB::table('failed_jobs')->whereIn('uuid', $ids)->count();

            if ($count === 0) {
                return ['success' => false, 'message' => 'No failed jobs to retry.'];
            }

            Artisan::call('queue:retry', ['id' => $ids]);

            ActivityLogger::log('job_retried', "Admin retried {$count} failed background " . Str::plural('job', $count), null, [
                'jobs' => $ids,
            ], null, 'system');

            return [
                'success' => true,
                'message' => "{$count} " . Str::plural('job', $count) . ' sent back to the queue. They run as soon as the queue worker picks them up.',
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to retry: ' . $e->getMessage()];
        }
    }

    public function deleteFailedJobs(array|string $uuids): array
    {
        try {
            if ($uuids === 'all') {
                $count = DB::table('failed_jobs')->count();
                Artisan::call('queue:flush');
            } else {
                $count = 0;
                foreach ((array) $uuids as $uuid) {
                    $count += Artisan::call('queue:forget', ['id' => $uuid]) === 0 ? 1 : 0;
                }
            }

            ActivityLogger::log('job_deleted', "Admin deleted {$count} failed background " . Str::plural('job', $count), null, [
                'jobs' => $uuids,
            ], null, 'system');

            return ['success' => true, 'message' => "{$count} failed " . Str::plural('job', $count) . ' deleted.'];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to delete: ' . $e->getMessage()];
        }
    }

    /** Readable name, target and short error for a failed_jobs row. */
    private function describeFailedJob(object $job, bool $withTrace = false): object
    {
        $payload = json_decode($job->payload, true) ?: [];
        $class = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Unknown job');
        $command = (string) ($payload['data']['command'] ?? '');

        $labels = [
            \App\Jobs\SendCompanyOfferDecisionEmail::class => 'Offer accepted/rejected email to company',
            \App\Jobs\SendCustomerWelcomeEmail::class => 'Welcome email to customer',
            \App\Mail\ContactMessageMail::class => 'Website contact form email',
            \App\Mail\CompanyRequestWaitingMail::class => 'Request reminder email to company',
            \App\Mail\CompanyOfferDecisionMail::class => 'Offer decision email',
            \App\Mail\CustomerWelcomeMail::class => 'Welcome email',
        ];

        // Recipient for queued mail ("to" is serialized inside the command).
        preg_match('/"address";s:\d+:"([^"]+)"/', $command, $to);

        $exception = (string) $job->exception;
        $firstLine = Str::limit(trim(strtok($exception, "\n")), 300);

        return (object) [
            'id' => $job->id,
            'uuid' => $job->uuid,
            'queue' => $job->queue,
            'connection' => $job->connection,
            'class' => $class,
            'label' => $labels[$class] ?? Str::headline(class_basename($class)),
            'recipient' => $to[1] ?? null,
            'attempts' => $payload['attempts'] ?? null,
            'max_tries' => $payload['maxTries'] ?? null,
            'error' => $firstLine,
            'hint' => $this->failedJobHint($exception),
            'exception' => $withTrace ? $exception : null,
            'failed_at' => Carbon::parse($job->failed_at),
        ];
    }

    /** Plain-language cause for the errors seen most often. */
    private function failedJobHint(string $exception): ?string
    {
        return match (true) {
            str_contains($exception, '535') || str_contains($exception, 'Failed to authenticate on SMTP') =>
                'Wrong email username/password for this mailer. Check the MAIL_* values in .env, run "php artisan config:cache", then retry.',
            str_contains($exception, 'is not defined') && str_contains($exception, 'Mailer') =>
                'The server is using an old cached config. Run "php artisan config:cache" and "php artisan queue:restart", then retry.',
            str_contains($exception, 'Connection could not be established') || str_contains($exception, 'timed out') =>
                'The mail server could not be reached. Check MAIL_HOST / MAIL_PORT, or try again later.',
            str_contains($exception, 'NotRegistered') || str_contains($exception, 'Requested entity was not found') =>
                'The phone is no longer registered for push notifications (app uninstalled). Safe to delete.',
            str_contains($exception, 'ModelNotFoundException') =>
                'The record this job was about has been deleted. Safe to delete.',
            default => null,
        };
    }

    //Background Jobs End

    //Notifications Start

    public function publishNotification(array $data): Notification
    {
        $startedAt = microtime(true);

        $context = [
            'sender_type' => $data['sender_type'] ?? 'admin',
            'audience_type' => $data['audience_type'] ?? 'selected',
        ];

        Log::info('SunXFinder notification: process started', $context);

        try {
            $result = (new Notification())
                ->getConnection()
                ->transaction(function () use ($data, $context) {
                    Log::info('SunXFinder notification: transaction started', $context);

                    $senderType = $data['sender_type'] ?? 'admin';
                    $senderId = $data['sender_id'] ?? null;
                    $audienceType = $data['audience_type'] ?? 'selected';

                    if ($senderType === 'company' && $audienceType === 'all') {
                        Log::warning(
                            'SunXFinder notification: company broadcast rejected',
                            $context
                        );

                        throw ValidationException::withMessages([
                            'audience_type' =>
                                'Companies cannot broadcast to all customers.',
                        ]);
                    }

                    Log::info('SunXFinder notification: resolving recipients', [
                        ...$context,
                        'provided_customer_count' =>
                            count($data['customer_ids'] ?? []),
                    ]);

                    $customerIds = match ($audienceType) {
                        'all' => Customer::query()
                            ->whereHas('deviceTokens')
                            ->pluck('id')
                            ->map(fn($id) => (int) $id)
                            ->all(),
                        'city' => Customer::query()
                            ->whereHas('deviceTokens')
                            ->where('city', $data['audience_city'] ?? '')
                            ->pluck('id')
                            ->map(fn($id) => (int) $id)
                            ->all(),
                        default => array_values(array_unique(
                            array_map('intval', $data['customer_ids'] ?? [])
                        )),
                    };

                    Log::info('SunXFinder notification: recipients resolved', [
                        ...$context,
                        'customer_count' => count($customerIds),
                    ]);

                    if (empty($customerIds)) {
                        Log::warning(
                            'SunXFinder notification: no recipients selected',
                            $context
                        );

                        throw ValidationException::withMessages([
                            'customer_ids' => $audienceType === 'city'
                                ? 'No app customers live in ' . ($data['audience_city'] ?? 'this city') . ' yet.'
                                : 'Select at least one customer.',
                        ]);
                    }

                    $existingIds = Customer::query()
                        ->whereIn('id', $customerIds)
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->all();

                    if (count($existingIds) !== count($customerIds)) {
                        Log::warning(
                            'SunXFinder notification: invalid customer IDs',
                            [
                                ...$context,
                                'requested_count' => count($customerIds),
                                'valid_count' => count($existingIds),
                            ]
                        );

                        throw ValidationException::withMessages([
                            'customer_ids' =>
                                'One or more selected customers are invalid.',
                        ]);
                    }

                    Log::info('SunXFinder notification: creating notification');

                    // Campaign extras (image, link, company, offer) are optional.
                    $campaignFields = array_intersect_key($data, array_flip([
                        'format', 'image_path', 'image_width', 'image_height',
                        'link_url', 'link_label_en', 'link_label_ar', 'company_id',
                        'offer_title_en', 'offer_title_ar', 'offer_price', 'offer_old_price',
                        'offer_currency', 'offer_expires_at', 'audience_city',
                    ]));

                    $notification = Notification::create([
                        'sender_type' => $senderType,
                        'sender_id' => $senderId,
                        'type' => $data['type'],
                        'title_en' => $data['title_en'],
                        'title_ar' => $data['title_ar'] ?? null,
                        'message_en' => $data['message_en'],
                        'message_ar' => $data['message_ar'] ?? null,
                        'audience_type' => $audienceType,
                        'status' => 'published',
                        'published_at' => now(),
                    ] + $campaignFields);

                    Log::info(
                        'SunXFinder notification: notification created',
                        [
                            'notification_id' => $notification->id,
                            ...$context,
                        ]
                    );

                    $now = now();
                    $insertedCount = 0;

                    foreach (array_chunk($customerIds, 500) as $chunk) {
                        $rows = array_map(
                            fn($customerId) => [
                                'notification_id' => $notification->id,
                                'customer_id' => $customerId,
                                'read_at' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ],
                            $chunk
                        );

                        CustomerNotification::insert($rows);
                        $insertedCount += count($rows);
                    }

                    Log::info(
                        'SunXFinder notification: recipient records inserted',
                        [
                            'notification_id' => $notification->id,
                            'recipient_count' => $insertedCount,
                        ]
                    );

                    return [
                        'notification' => $notification,
                        'customerIds' => $customerIds,
                    ];
                });

            Log::info('SunXFinder notification: transaction committed', [
                'notification_id' => $result['notification']->id,
                'customer_count' => count($result['customerIds']),
            ]);
        } catch (Throwable $e) {
            Log::error('SunXFinder notification: database/process failure', [
                ...$context,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }

        $notification = $result['notification'];
        $customerIds = $result['customerIds'];

        Log::info('SunXFinder notification: looking up device tokens', [
            'notification_id' => $notification->id,
            'customer_count' => count($customerIds),
        ]);

        try {
            $devices = CustomerDeviceToken::query()
                ->whereIn('customer_id', $customerIds)
                ->get([
                    'id',
                    'customer_id',
                    'fcm_token',
                    'lang',
                    'platform',
                ]);

            Log::info('SunXFinder notification: device tokens loaded', [
                'notification_id' => $notification->id,
                'device_count' => $devices->count(),
                'customer_count_with_devices' =>
                    $devices->pluck('customer_id')->unique()->count(),
            ]);
        } catch (Throwable $e) {
            Log::error('SunXFinder notification: device lookup failed', [
                'notification_id' => $notification->id,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if ($devices->isEmpty()) {
            Log::warning('SunXFinder notification: no device tokens found', [
                'notification_id' => $notification->id,
                'customer_count' => count($customerIds),
            ]);

            return $notification;
        }

        try {
            Log::info('SunXFinder notification: initializing Firebase messaging', [
                'notification_id' => $notification->id,
            ]);

            $messaging = app(Messaging::class);

            Log::info('SunXFinder notification: Firebase messaging initialized', [
                'notification_id' => $notification->id,
            ]);
        } catch (Throwable $e) {
            Log::critical('SunXFinder notification: Firebase initialization failed', [
                'notification_id' => $notification->id,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }

        $successCount = 0;
        $failureCount = 0;
        $helper = app(Helper::class);
        $imageUrl = $notification->imageUrl();

        // One multicast per language and per 500 devices (the FCM limit), instead of
        // one HTTP call per device: a broadcast to thousands of devices stays fast.
        $devicesByLang = $devices->groupBy(
            fn($device) => strtolower((string) $device->lang) === 'ar' ? 'ar' : 'en'
        );

        foreach ($devicesByLang as $lang => $langDevices) {
            $title = $lang === 'ar'
                ? ($notification->title_ar ?: $notification->title_en)
                : $notification->title_en;

            $message = $lang === 'ar'
                ? ($notification->message_ar ?: $notification->message_en)
                : $notification->message_en;

            // Pushes are plain text: rich (HTML) messages show in full inside the app.
            $body = Str::limit($helper->plainText($message), 230);

            $data = array_filter([
                'notification_id' => (string) $notification->id,
                'type' => (string) $notification->type,
                'lang' => $lang,
                'image_url' => (string) $imageUrl,
                'link_url' => (string) $notification->link_url,
                'company_id' => $notification->company_id ? (string) $notification->company_id : '',
            ], fn($value) => $value !== '');

            $pushMessage = Helper::withNotificationSound(CloudMessage::new())
                ->withNotification(FcmNotification::create($title, $body, $imageUrl))
                ->withData($data);

            foreach ($langDevices->pluck('fcm_token')->unique()->chunk(500) as $tokens) {
                try {
                    $report = $messaging->sendMulticast($pushMessage, $tokens->values()->all());

                    $successCount += $report->successes()->count();
                    $failureCount += $report->failures()->count();
                } catch (Throwable $e) {
                    $failureCount += $tokens->count();

                    Log::error('SunXFinder notification: push batch failed', [
                        'notification_id' => $notification->id,
                        'lang' => $lang,
                        'device_count' => $tokens->count(),
                        'exception_class' => get_class($e),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('SunXFinder notification: delivery process completed', [
            'notification_id' => $notification->id,
            'device_count' => $devices->count(),
            'firebase_accepted_count' => $successCount,
            'failed_count' => $failureCount,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000),
        ]);

        return $notification;
    }


    public function getNotifications(array $filters = []): array
    {
        $query = Notification::query()
            ->withCount('recipients')
            ->with(['senderCompany:id,name', 'senderAdmin:id,username', 'company:id,name'])
            ->when(
                !empty($filters['type']),
                fn(Builder $q) => $q->where('type', $filters['type'])
            )
            ->when(
                !empty($filters['status']),
                fn(Builder $q) => $q->where('status', $filters['status'])
            )
            ->when(!empty($filters['search']), function (Builder $q) use ($filters) {
                $search = $filters['search'];

                $q->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('title_en', 'like', "%{$search}%")
                        ->orWhere('title_ar', 'like', "%{$search}%")
                        ->orWhere('message_en', 'like', "%{$search}%")
                        ->orWhere('message_ar', 'like', "%{$search}%");
                });
            });

        $notifications = $query
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $publishedCount = Notification::where('status', 'published')->count();
        $draftCount = Notification::where('status', 'draft')->count();

        $recipientCount = DB::table('tbl_customer_notifications')
            ->count();

        return [
            'notifications' => $notifications,
            'publishedCount' => $publishedCount,
            'draftCount' => $draftCount,
            'recipientCount' => $recipientCount,
        ];
    }

    /**
     * Soft-deletes a notification (admin or company sent) and its customer copies,
     * so it also disappears from the app and from unread counts.
     */
    public function deleteNotification(int $id): array
    {
        $notification = Notification::query()->find($id);

        if (!$notification) {
            return ['success' => false, 'message' => 'Notification not found.'];
        }

        try {
            DB::transaction(function () use ($notification) {
                app(Helper::class)->archiveItem($notification, 'notification', null, 'website');
                $customerNotifications = CustomerNotification::query()
                    ->where('notification_id', $notification->id)
                    ->get();

                foreach ($customerNotifications as $customerNotification) {
                    app(Helper::class)->archiveItem(
                        $customerNotification,
                        'customer_notification',
                        (int) $customerNotification->customer_id,
                        'website'
                    );
                }

                CustomerNotification::query()->where('notification_id', $notification->id)->delete();
                $notification->delete();
            });
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to delete the notification. Please try again.'];
        }

        return ['success' => true, 'message' => 'Notification deleted. Customers no longer see it in the app.'];
    }

    public function getNotificationFormData(): array
    {
        $customers = Customer::query()
            ->whereHas('deviceTokens')
            ->select('id', 'full_name', 'username', 'phone')
            ->orderBy('username')
            ->get();

        return [
            'customers' => $customers,
        ];
    }

    private const CAMPAIGN_IMAGE_DIR = 'main/images/notifications';

    /** Companies to feature, cities to target and app customers for the campaign composer. */
    public function getCampaignFormData(): array
    {
        $customers = Customer::query()
            ->whereHas('deviceTokens')
            ->select('id', 'full_name', 'username', 'phone', 'city')
            ->orderBy('full_name')
            ->get();

        $cities = $customers
            ->filter(fn($customer) => trim((string) $customer->city) !== '')
            ->groupBy(fn($customer) => trim($customer->city))
            ->map(fn($group) => $group->count())
            ->sortKeys();

        $companies = app(Helper::class)->visibleCompanies()
            ->select('id', 'name', 'city', 'logo_path')
            ->orderBy('name')
            ->get();

        return [
            'customers' => $customers,
            'cities' => $cities,
            'companies' => $companies,
            'appCustomerCount' => $customers->count(),
        ];
    }

    /**
     * Rich campaign: stores the image, sanitizes HTML and sends it through the normal
     * notification pipeline (inbox rows + push in each device's language).
     */
    public function publishCampaign(array $data, ?\Illuminate\Http\UploadedFile $image, ?int $adminId): Notification
    {
        $helper = app(Helper::class);

        if (($data['format'] ?? 'text') === 'html') {
            $data['message_en'] = Helper::sanitizeRichText($data['message_en']);
            $data['message_ar'] = Helper::sanitizeRichText($data['message_ar'] ?? '') ?: null;

            if ($helper->plainText($data['message_en']) === '') {
                throw ValidationException::withMessages(['message_en' => 'The English message is empty after removing unsupported HTML.']);
            }
        }

        if (empty($data['has_offer'])) {
            foreach (['offer_title_en', 'offer_title_ar', 'offer_price', 'offer_old_price', 'offer_currency', 'offer_expires_at'] as $field) {
                $data[$field] = null;
            }
        }
        if (empty($data['has_link'])) {
            $data['link_url'] = $data['link_label_en'] = $data['link_label_ar'] = null;
        }

        $stored = null;
        if ($image) {
            $stored = Helper::storeOptimizedImage($image, self::CAMPAIGN_IMAGE_DIR, 1200);
            $data['image_path'] = $stored['path'];
            $data['image_width'] = $stored['width'];
            $data['image_height'] = $stored['height'];
        }

        $data['sender_type'] = 'admin';
        $data['sender_id'] = $adminId;

        try {
            return $this->publishNotification($data);
        } catch (Throwable $e) {
            // Nothing was published: don't leave the uploaded image behind. (A push
            // failure after the save keeps it, since the inbox item uses it.)
            if ($stored && !Notification::withTrashed()->where('image_path', $stored['path'])->exists()) {
                \Illuminate\Support\Facades\File::delete(public_path($stored['path']));
            }

            throw $e;
        }
    }


    public function getNotificationDetails(int $id): array
    {
        $notification = Notification::query()
            ->withCount('recipients')
            ->find($id);

        if (!$notification) {
            return [
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'الإشعار غير موجود.'
                    : 'Notification not found.',
            ];
        }

        $readCount = CustomerNotification::query()
            ->where('notification_id', $id)
            ->whereNotNull('read_at')
            ->count();

        $unreadCount = max(
            0,
            $notification->recipients_count - $readCount
        );

        $recipients = CustomerNotification::query()
            ->with([
                'customer:id,full_name,phone',
                'customer.deviceTokens:id,customer_id,fcm_token,lang,platform',
            ])
            ->where('notification_id', $id)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return [
            'success' => true,
            'notification' => $notification,
            'readCount' => $readCount,
            'unreadCount' => $unreadCount,
            'recipients' => $recipients,
        ];
    }


    //Notifications End

    //Ratings Start

    public function getRatings(array $filters = []): LengthAwarePaginator
    {
        return Rating::query()
            ->with([
                'customer:id,full_name,username,city',
                'company:id,name,city',
                'quote:id,quote_name',
            ])
            ->withCount('answers')
            ->when(!empty($filters['company_id']), fn($q) => $q->where('company_id', (int) $filters['company_id']))
            ->when(!empty($filters['stars']), fn($q) => $q->where('stars', (int) $filters['stars']))
            ->when(($filters['visibility'] ?? '') === 'visible', fn($q) => $q->where('is_visible', true))
            ->when(($filters['visibility'] ?? '') === 'hidden', fn($q) => $q->where('is_visible', false))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search)
                            ->orWhere('quote_id', (int) $search);
                    }

                    $q->orWhere('comment', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        })
                        ->orWhereHas('company', fn($companyQuery) => $companyQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function getRatingMetrics(): array
    {
        $visible = Rating::query()->visible();

        $distribution = (clone $visible)
            ->selectRaw('stars, COUNT(*) as n')
            ->groupBy('stars')
            ->pluck('n', 'stars');

        $average = (clone $visible)->avg('stars');

        return [
            'total' => Rating::query()->count(),
            'average' => $average !== null ? round((float) $average, 1) : null,
            'this_month' => Rating::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            'hidden' => Rating::query()->where('is_visible', false)->count(),
            'rated_companies' => (clone $visible)->distinct()->count('company_id'),
            // stars => count, 5 down to 1, zeros included.
            'distribution' => collect(range(Rating::MAX_STARS, Rating::MIN_STARS))
                ->mapWithKeys(fn($stars) => [$stars => (int) ($distribution[$stars] ?? 0)])
                ->all(),
        ];
    }

    public function getRatingById(int $id): ?Rating
    {
        return Rating::query()
            ->with([
                'customer:id,full_name,username,city,phone,email',
                'company:id,name,city,slug',
                'quote:id,quote_name,created_at',
                'project:id,status,progress_percent',
                'answers' => fn($q) => $q->with('question')->orderBy('question_id'),
            ])
            ->find($id);
    }

    /** Hide or show a rating. Hidden ratings leave the company average and public page. */
    public function setRatingVisibility(int $id, bool $visible): array
    {
        try {
            $rating = Rating::find($id);

            if (!$rating) {
                return ['success' => false, 'message' => 'Rating not found.'];
            }

            $rating->is_visible = $visible;
            $rating->save();

            return [
                'success' => true,
                'message' => $visible
                    ? 'Rating is visible again and counts in the company average.'
                    : 'Rating hidden. It no longer shows publicly or counts in the company average.',
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to update the rating. Please try again.'];
        }
    }

    /** Deleting lets the customer rate that request again. */
    public function deleteRating(int $id): array
    {
        try {
            $rating = Rating::find($id);

            if (!$rating) {
                return ['success' => false, 'message' => 'Rating not found.'];
            }

            DB::transaction(function () use ($rating) {
                foreach ($rating->answers as $answer) {
                    app(Helper::class)->archiveItem($answer, 'rating_answer', null, 'website');
                }
                app(Helper::class)->archiveItem($rating, 'rating', (int) $rating->customer_id, 'website');
                $rating->answers()->delete();
                $rating->delete();
            });

            return ['success' => true, 'message' => 'Rating deleted. The customer can rate this request again.'];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to delete the rating. Please try again.'];
        }
    }

    public function getRatingCompanyOptions()
    {
        return SolarCompany::query()
            ->whereHas('ratings')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    //Ratings End

    //Rating Questions Start

    public function getRatingQuestions()
    {
        return RatingQuestion::query()
            ->withCount('answers')
            ->withAvg('answers', 'value')
            ->ordered()
            ->get();
    }

    public function findRatingQuestion(int $id): ?RatingQuestion
    {
        return RatingQuestion::find($id);
    }

    public function createRatingQuestion(array $data): array
    {
        try {
            $question = new RatingQuestion();
            $this->assignRatingQuestionFields($question, $data);
            $question->save();

            return ['success' => true, 'message' => "Question \"{$question->title_en}\" added successfully."];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to save the question. Please try again.'];
        }
    }

    public function updateRatingQuestion(int $id, array $data): array
    {
        try {
            $question = RatingQuestion::find($id);

            if (!$question) {
                return ['success' => false, 'message' => 'Question not found.'];
            }

            $this->assignRatingQuestionFields($question, $data);
            $question->save();

            return ['success' => true, 'message' => "Question \"{$question->title_en}\" updated successfully."];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to update the question. Please try again.'];
        }
    }

    /** Soft delete: past answers keep showing the question title. */
    public function deleteRatingQuestion(int $id): array
    {
        try {
            $question = RatingQuestion::find($id);

            if (!$question) {
                return ['success' => false, 'message' => 'Question not found.'];
            }

            app(Helper::class)->archiveItem($question, 'rating_question', null, 'website');
            $question->delete();

            return ['success' => true, 'message' => 'Question deleted. Past answers to it are kept.'];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to delete the question. Please try again.'];
        }
    }

    private function assignRatingQuestionFields(RatingQuestion $question, array $data): void
    {
        $question->title_en = $data['title_en'];
        $question->title_ar = $data['title_ar'];
        $question->question_en = $data['question_en'] ?? null;
        $question->question_ar = $data['question_ar'] ?? null;
        $question->sort_order = (int) ($data['sort_order'] ?? 0);
        $question->is_active = (bool) ($data['is_active'] ?? false);
    }

    //Rating Questions End

    //Rating Values Start

    public function getRatingValues()
    {
        return RatingValue::query()->orderBy('value')->get();
    }

    /** Labels only; the 1-4 scale itself is fixed. $values = [value => [label_en, label_ar]]. */
    public function updateRatingValues(array $values): array
    {
        try {
            DB::transaction(function () use ($values) {
                foreach ($values as $value => $labels) {
                    RatingValue::query()->updateOrCreate(
                        ['value' => (int) $value],
                        ['label_en' => $labels['label_en'], 'label_ar' => $labels['label_ar']]
                    );
                }
            });

            return ['success' => true, 'message' => 'Rating values saved successfully.'];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to save the rating values. Please try again.'];
        }
    }

    //Rating Values End

    //Settings Start

    public function getSettings(): array
    {
        cache()->forget(Setting::MAINTENANCE_CACHE_KEY);

        return [
            'max_open_quotes' => Setting::maxOpenQuotes(),
            'maintenance' => Setting::maintenance(),
            'quote_limit_customers' => $this->getQuoteLimitCustomers(),
            'api_endpoints' => ApiEndpoint::query()->orderByDesc('is_active')->orderBy('name')->get(),
        ];
    }

    //Send Email Start

    /** Companies offered as suggestions in the "Email to" field. */
    public function getCompanyEmailOptions()
    {
        return SolarCompany::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /** Sends the admin's email from info@ (bcc to the owner) and logs it. */
    public function sendCompanyEmail(array $data): array
    {
        $sent = [];
        $failed = [];

        foreach ($data['emails'] as $email) {
            try {
                $companyName = $data['company_name'] ?? SolarCompany::query()->where('email', $email)->value('name');

                Mail::to($email)->send(new CompanyJoinRequestMail(
                    $data['subject'],
                    $data['message'],
                    $data['lang'],
                    $companyName
                ));
                SentEmail::query()->create([
                    'recipient_email' => $email,
                    'recipient_name' => $companyName,
                    'subject' => $data['subject'],
                    'message' => $data['message'],
                    'lang' => $data['lang'],
                    'sent_at' => now(),
                ]);
                $sent[] = $email;
            } catch (Throwable $e) {
                report($e);
                $failed[] = $email;
            }
        }

        if ($sent) {
            ActivityLogger::log('email_sent', 'Admin emailed ' . count($sent) . ' recipient(s): ' . $data['subject'], null, [
                'to' => $sent,
                'subject' => $data['subject'],
                'lang' => $data['lang'],
            ], null, 'notifications');
        }

        if (! $sent) {
            return [
                'success' => false,
                'message' => 'The email could not be sent to any of the selected recipients.',
            ];
        }

        if ($failed) {
            return [
                'success' => false,
                'message' => 'Sent to ' . count($sent) . ' recipient(s). Failed: ' . implode(', ', $failed)
                    . '. Retry only the failed addresses.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Email sent separately to ' . count($sent) . ' recipient(s).',
        ];
    }

    //Send Email End

    //API Endpoints Start

    public function createApiEndpoint(array $data): array
    {
        try {
            $endpoint = DB::transaction(function () use ($data) {
                // The first endpoint becomes the active one, so one is always active.
                $isFirst = !ApiEndpoint::query()->exists();

                return ApiEndpoint::create($this->apiEndpointFields($data) + ['is_active' => $isFirst]);
            });

            cache()->forget(ApiEndpoint::CONFIG_CACHE_KEY);

            return ['success' => true, 'message' => "API {$endpoint->name} added."];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to add the API. Please try again.'];
        }
    }

    public function updateApiEndpoint(int $id, array $data): array
    {
        try {
            $endpoint = ApiEndpoint::find($id);

            if (!$endpoint) {
                return ['success' => false, 'message' => 'API not found.'];
            }

            $endpoint->update($this->apiEndpointFields($data));
            cache()->forget(ApiEndpoint::CONFIG_CACHE_KEY);

            // The app now talks to a new URL: old tokens are useless there, so customers log in again.
            $signedOut = $endpoint->is_active && $endpoint->wasChanged('base_url')
                ? $this->revokeAllCustomerSessions()
                : 0;

            return [
                'success' => true,
                'message' => "API {$endpoint->name} saved" . ($endpoint->is_online ? ' (online).' : ' (offline).')
                    . ($signedOut ? " {$signedOut} customer(s) were signed out and must log in again." : ''),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to save the API. Please try again.'];
        }
    }

    /** Makes this endpoint the only active one, in one transaction (never two at once). */
    public function activateApiEndpoint(int $id): array
    {
        try {
            $endpoint = DB::transaction(function () use ($id) {
                $endpoint = ApiEndpoint::query()->lockForUpdate()->find($id);

                if (!$endpoint) {
                    return null;
                }

                ApiEndpoint::query()->whereKeyNot($id)->where('is_active', true)->update(['is_active' => false]);
                $endpoint->update(['is_active' => true]);

                return $endpoint;
            });

            if (!$endpoint) {
                return ['success' => false, 'message' => 'API not found.'];
            }

            cache()->forget(ApiEndpoint::CONFIG_CACHE_KEY);

            // Switched to another API: customers log in again against it.
            $signedOut = $endpoint->wasChanged('is_active') ? $this->revokeAllCustomerSessions() : 0;

            return [
                'success' => true,
                'message' => "The app now uses {$endpoint->name} ({$endpoint->base_url})."
                    . ($endpoint->is_online ? '' : ' It is offline, so the app shows the offline message until you turn it online.')
                    . ($signedOut ? " {$signedOut} customer(s) were signed out and must log in again." : ''),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to change the active API. Please try again.'];
        }
    }

    public function deleteApiEndpoint(int $id): array
    {
        try {
            $endpoint = ApiEndpoint::find($id);

            if (!$endpoint) {
                return ['success' => false, 'message' => 'API not found.'];
            }

            if ($endpoint->is_active) {
                return ['success' => false, 'message' => 'The active API cannot be deleted. Make another API active first.'];
            }

            app(Helper::class)->archiveItem($endpoint, 'api_endpoint', null, 'website');
            $endpoint->delete();
            cache()->forget(ApiEndpoint::CONFIG_CACHE_KEY);

            return ['success' => true, 'message' => "API {$endpoint->name} deleted."];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to delete the API. Please try again.'];
        }
    }

    private function apiEndpointFields(array $data): array
    {
        return [
            'name' => strtoupper(trim($data['name'])),
            'base_url' => rtrim(trim($data['base_url']), '/'),
            'is_online' => (bool) ($data['is_online'] ?? false),
            'offline_message_en' => $data['offline_message_en'] ?? null,
            'offline_message_ar' => $data['offline_message_ar'] ?? null,
        ];
    }

    //API Endpoints End

    /**
     * Customers who may get a higher open-request limit: registered (active) and with
     * at least one completed project. Customers already raised come first.
     */
    public function getQuoteLimitCustomers()
    {
        $completedProjects = Project::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('tbl_projects.customer_id', 'tbl_customers.id')
            ->where('status', 'completed');

        return Customer::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereExists(fn($q) => $q->from('tbl_projects')
                ->whereColumn('tbl_projects.customer_id', 'tbl_customers.id')
                ->where('status', 'completed')
                ->whereNull('tbl_projects.deleted_at'))
            ->select(['id', 'username', 'full_name', 'phone', 'max_open_quotes'])
            ->selectSub($completedProjects, 'completed_projects')
            ->orderByRaw('max_open_quotes IS NULL')
            ->orderBy('full_name')
            ->get();
    }

    public function setCustomerQuoteLimit(int $customerId, ?int $limit): array
    {
        try {
            $customer = $this->getQuoteLimitCustomers()->firstWhere('id', $customerId);

            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Only registered customers with at least one completed project can get a higher limit.',
                ];
            }

            $default = Setting::maxOpenQuotes();
            $name = $customer->full_name ?: $customer->username;

            // Removing, or choosing the default itself, just goes back to the default.
            $customer = Customer::find($customerId);
            $customer->max_open_quotes = $limit !== null && $limit !== $default ? $limit : null;
            $customer->save();

            $own = $customer->max_open_quotes;

            return [
                'success' => true,
                'message' => $own
                    ? "{$name} can now have up to {$own} open " . Str::plural('request', $own) . '.'
                    : "{$name} is back to the default limit of {$default} open " . Str::plural('request', $default) . '.',
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to update the customer limit. Please try again.'];
        }
    }

    public function updateSettings(array $data): array
    {
        try {
            $wasInMaintenance = Setting::maintenance()['enabled'];

            Setting::set('max_open_quotes', (int) $data['max_open_quotes']);

            Setting::set('maintenance_enabled', !empty($data['maintenance_enabled']) ? '1' : '0');
            foreach (['title_en', 'title_ar', 'message_en', 'message_ar', 'until'] as $field) {
                Setting::set("maintenance_{$field}", $data["maintenance_{$field}"] ?? null);
            }

            cache()->forget(Setting::MAINTENANCE_CACHE_KEY);

            $nowInMaintenance = !empty($data['maintenance_enabled']);

            return [
                'success' => true,
                'message' => 'Settings saved successfully.' . match (true) {
                    $nowInMaintenance && !$wasInMaintenance => ' Maintenance mode is ON: visitors now see the maintenance page.',
                    !$nowInMaintenance && $wasInMaintenance => ' Maintenance mode is OFF: the website is live again.',
                    default => '',
                },
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to save the settings. Please try again.'];
        }
    }

    //Settings End

    //Database Backups Start

    public function getDatabaseBackups(): array
    {
        return DatabaseBackup::all();
    }

    public function createDatabaseBackup(): array
    {
        try {
            $started = microtime(true);
            $name = DatabaseBackup::create();
            $size = filesize(DatabaseBackup::path($name));

            ActivityLogger::log('created', "Admin created database backup {$name}", null, [
                'file' => $name,
                'size_bytes' => $size,
            ], null, 'system');

            return [
                'success' => true,
                'message' => "Backup {$name} created (" . $this->humanSize($size) . ', ' . round(microtime(true) - $started, 1) . 's).',
            ];
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()];
        }
    }

    public function getDatabaseBackupPath(string $name): ?string
    {
        return DatabaseBackup::path($name);
    }

    public function deleteDatabaseBackup(string $name): array
    {
        if (!DatabaseBackup::delete($name)) {
            return ['success' => false, 'message' => 'Backup not found.'];
        }

        ActivityLogger::log('deleted', "Admin deleted database backup {$name}", null, ['file' => $name], null, 'system');

        return ['success' => true, 'message' => "Backup {$name} deleted."];
    }

    private function humanSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1048576 => round($bytes / 1048576, 1) . ' MB',
            $bytes >= 1024 => round($bytes / 1024, 1) . ' KB',
            default => $bytes . ' B',
        };
    }

    //Database Backups End

    //Activity Logs Start

    public function getActivityLogs(array $filters = []): LengthAwarePaginator
    {
        return $this->activityLogsQuery($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
    }

    /** Same filters as the list, oldest first, streamed for the CSV export. */
    public function activityLogsForExport(array $filters = [])
    {
        return $this->activityLogsQuery($filters)
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursor();
    }

    public function getActivityLogById(int $id): ?ActivityLog
    {
        return ActivityLog::find($id);
    }

    public function getActivityLogMetrics(): array
    {
        $today = now()->startOfDay();
        $todayLogs = ActivityLog::query()->where('created_at', '>=', $today);

        $byActor = (clone $todayLogs)
            ->selectRaw('actor_type, COUNT(*) as n')
            ->groupBy('actor_type')
            ->pluck('n', 'actor_type');

        return [
            'today' => (clone $todayLogs)->count(),
            'logins_today' => (clone $todayLogs)->where('action', 'login')->count(),
            'failed_today' => (clone $todayLogs)->whereIn('action', ['login_failed', 'login_blocked'])->count(),
            'deletions_today' => (clone $todayLogs)->whereIn('action', ['deleted', 'force_deleted', 'account_deleted'])->count(),
            'by_actor' => collect(ActivityLog::ACTOR_TYPES)
                ->mapWithKeys(fn($label, $type) => [$type => (int) ($byActor[$type] ?? 0)])
                ->all(),
            'total' => ActivityLog::query()->count(),
        ];
    }

    private function activityLogsQuery(array $filters)
    {
        return ActivityLog::query()
            ->when(!empty($filters['actor_type']), fn($q) => $q->where('actor_type', $filters['actor_type']))
            ->when(!empty($filters['actor_id']), fn($q) => $q->where('actor_id', (int) $filters['actor_id']))
            ->when(!empty($filters['action']), fn($q) => $q->where('action', $filters['action']))
            ->when(!empty($filters['category']), fn($q) => $q->where('category', $filters['category']))
            ->when(!empty($filters['subject_type']), fn($q) => $q->where('subject_type', $filters['subject_type']))
            ->when(!empty($filters['subject_id']), fn($q) => $q->where('subject_id', (int) $filters['subject_id']))
            ->when(!empty($filters['from']), fn($q) => $q->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay()))
            ->when(!empty($filters['to']), fn($q) => $q->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay()))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('actor_name', 'like', "%{$search}%")
                        ->orWhere('subject_label', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            });
    }

    //Activity Logs End

    public function getArchivedItems(array $filters = []): LengthAwarePaginator
    {
        return ArchivedItem::query()
            ->with('customer')
            ->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['source']), fn($q) => $q->where('source', $filters['source']))
            ->when(!empty($filters['customer_id']), fn($q) => $q->where('customer_id', (int) $filters['customer_id']))
            ->when(!empty($filters['from']), fn($q) => $q->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay()))
            ->when(!empty($filters['to']), fn($q) => $q->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay()))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('type', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $q->orWhere('item_id', (int) $search)
                            ->orWhere('customer_id', (int) $search);
                    }
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
    }

    public function deleteArchivedItem(ArchivedItem $item): void
    {
        $item->delete();
    }
}
