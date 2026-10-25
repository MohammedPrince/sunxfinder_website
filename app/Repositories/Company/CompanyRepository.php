<?php

namespace App\Repositories\Company;

use App\Helpers\Helper;
use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Rating;
use App\Models\SolarCompany;
use App\Repositories\OtpRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use App\Support\ReportPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CustomerDeviceToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;


class CompanyRepository
{
    protected OtpRepository $otpRepository;
    protected Helper $helper;

    public function __construct(OtpRepository $otpRepository, Helper $helper)
    {
        $this->otpRepository = $otpRepository;
        $this->helper = $helper;
    }


    //Company profile START

    public function getCompany(int $id): array
    {
        try {
            $company = SolarCompany::find($id);

            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Company retrieved successfully.',
                'company' => $company,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to retrieve company details.',
            ];
        }
    }


    public function updateCompanyProfile(int $id, array $data): array
    {
        try {
            $company = SolarCompany::find($id);

            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found.',
                ];
            }

            $company->name = $data['name'];
            $company->city = $data['city'];
            $company->license_number = $data['license_number'];
            $company->description = $data['description'];
            $company->ar_description = $data['ar_description'] ?? null;
            $company->services = $data['services'];
            $company->ar_services = $data['ar_services'] ?? null;
            $company->phone = $data['phone'];
            $company->email = $data['email'];
            $company->social_media = $data['social_media'] ?? null;
            $company->year_of_exp = $data['year_of_exp'] ?? null;
            $company->number_of_projects = $data['number_of_projects'] ?? null;

            // Update password only when a new password is provided.
            // The password is already hashed by the controller.
            if (!empty($data['password'])) {
                $company->password = $data['password'];
            }

            // Update logo only when a new logo is provided.
            if (!empty($data['logo_path'])) {
                $company->logo_path = $data['logo_path'];
            }

            if (!$company->save()) {
                return [
                    'success' => false,
                    'message' => 'Unable to update the company profile.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Company profile updated successfully.',
                'company' => $company->fresh(),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'An error occurred while updating the company profile.',
            ];
        }
    }

    public function paginateForCompany(
        int $companyId,
        array $filters = []
    ): LengthAwarePaginator {
        $query = Product::query()
            ->where('company_id', $companyId)
            ->with(['supplier', 'specs'])
            ->latest();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        return $query->paginate(10)->withQueryString();
    }

    public function findForCompany(
        int $companyId,
        int $productId
    ): Product {
        return Product::query()
            ->where('company_id', $companyId)
            ->with(['supplier', 'specs'])
            ->findOrFail($productId);
    }

    public function createForCompany(
        int $companyId,
        array $data
    ): Product {
        $product = new Product();

        // Never accept company_id from the submitted form.
        $product->company_id = $companyId;
        $product->supplier_id = $data['supplier_id'];
        $product->name = $data['name'];
        $product->model = $data['model'] ?? null;
        $product->type = $data['type'];
        $product->description = $data['description'] ?? null;
        $product->is_active = $data['is_active'] ?? true;
        $product->save();

        return $product;
    }

    public function updateForCompany(
        int $companyId,
        int $productId,
        array $data
    ): Product {
        $product = $this->findForCompany($companyId, $productId);

        $product->supplier_id = $data['supplier_id'];
        $product->name = $data['name'];
        $product->model = $data['model'] ?? null;
        $product->type = $data['type'];
        $product->description = $data['description'] ?? null;
        $product->is_active = $data['is_active'] ?? false;
        $product->save();

        return $product;
    }

    public function deleteForCompany(
        int $companyId,
        int $productId
    ): bool {
        $product = $this->findForCompany($companyId, $productId);

        return (bool) $product->delete();
    }

    //Company profile END


    //Password Recovery Start

    public function companyEmailExists(string $email): bool
    {
        return SolarCompany::query()->where('email', $email)->exists();
    }

    public function sendPasswordResetOtp(string $email, string $locale = 'en'): array
    {
        $company = SolarCompany::query()->where('email', $email)->first();

        if (!$company) {
            return ['success' => false, 'message' => 'No company account is registered with this email.'];
        }

        return $this->otpRepository->sendPasswordResetCode(
            'company',
            $email,
            (string) ($company->name ?: $company->username),
            'company',
            $locale
        );
    }

    public function resetPasswordWithOtp(string $email, string $code, string $password): array
    {
        $check = $this->otpRepository->verifyPasswordResetCode('company', $email, $code);

        if (!$check['success']) {
            return $check;
        }

        $company = SolarCompany::query()->where('email', $email)->first();

        if (!$company) {
            return ['success' => false, 'message' => 'No company account is registered with this email.'];
        }

        // The "hashed" cast hashes the new password; a new remember token signs out other devices.
        $company->password = $password;
        $company->setRememberToken(Str::random(60));
        $company->save();

        $this->otpRepository->consume($check['otp']);

        return ['success' => true, 'message' => 'Your password has been reset. Sign in with your new password.'];
    }

    //Password Recovery End

    //Dashboard Start

    public function getDashboardData(): array
    {
        $companyId = $this->authenticatedCompanyId();
        $company = Auth::guard('company')->user();

        // Requests assigned to this company, open to all, or already offered on.
        $visibleQuotes = Quote::query()->visibleToCompany($companyId);

        $pendingRequests = (clone $visibleQuotes)
            ->where('status', 'pending')
            ->count();

        $awaitingOffer = (clone $visibleQuotes)
            ->where('status', 'pending')
            ->whereDoesntHave('companyQuotations', function ($quotationQuery) use ($companyId) {
                $quotationQuery->where('company_id', $companyId);
            })
            ->count();

        $offerCounts = CompanyQuotation::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $offers = [
            'selected' => (int) ($offerCounts['selected'] ?? 0),
            'submitted' => (int) ($offerCounts['submitted'] ?? 0),
            'not_selected' => (int) ($offerCounts['not_selected'] ?? 0),
            'withdrawn' => (int) ($offerCounts['withdrawn'] ?? 0),
        ];
        $offers['total'] = array_sum($offers);

        // Win rate only counts offers the customer has already decided on.
        $decidedOffers = $offers['selected'] + $offers['not_selected'];
        $offers['win_rate'] = $decidedOffers > 0
            ? (int) round($offers['selected'] / $decidedOffers * 100)
            : null;

        $projectCounts = Project::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $projects = [
            'accepted' => (int) ($projectCounts['accepted'] ?? 0),
            'in_progress' => (int) ($projectCounts['in_progress'] ?? 0),
            'completed' => (int) ($projectCounts['completed'] ?? 0),
        ];
        $projects['active'] = $projects['accepted'] + $projects['in_progress'];
        $projects['average_progress'] = (int) round((float) Project::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['accepted', 'in_progress'])
            ->avg('progress_percent'));

        $products = [
            'total' => Product::query()->where('company_id', $companyId)->count(),
            'active' => Product::query()->where('company_id', $companyId)->where('is_active', true)->count(),
        ];

        // Daily activity for the last 7 days, including today.
        $start = now()->subDays(6)->startOfDay();

        $requestsPerDay = (clone $visibleQuotes)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $offersPerDay = CompanyQuotation::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $activity = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();

            $activity[] = [
                'label' => $date->format('D'),
                'date' => $date->format('d M'),
                'requests' => (int) ($requestsPerDay[$key] ?? 0),
                'offers' => (int) ($offersPerDay[$key] ?? 0),
            ];
        }

        $latestRequests = (clone $visibleQuotes)
            ->with([
                'customer',
                'companyQuotations' => function ($quotationQuery) use ($companyId) {
                    $quotationQuery
                        ->where('company_id', $companyId)
                        ->select(['id', 'quote_id', 'company_id', 'price', 'currency', 'status']);
                },
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'company' => $company,
            'pendingRequests' => $pendingRequests,
            'awaitingOffer' => $awaitingOffer,
            'offers' => $offers,
            'projects' => $projects,
            'products' => $products,
            'activity' => $activity,
            'latestRequests' => $latestRequests,
        ];
    }

    /**
     * Top bar: what this company should act on, plus today's activity.
     * Runs on every company page, so it only does a few count queries.
     */
    public function getTopbarData(): array
    {
        $companyId = $this->authenticatedCompanyId();
        $company = SolarCompany::query()->find($companyId, ['id', 'status', 'is_verified', 'slug']);
        $today = now()->startOfDay();

        $projects = Project::query()->where('company_id', $companyId);

        $lastUpdate = DB::table('tbl_project_updates')
            ->whereNull('deleted_at')
            ->selectRaw('project_id, MAX(created_at) as last_update_at')
            ->groupBy('project_id');

        $idleProjects = DB::table('tbl_projects as p')
            ->leftJoinSub($lastUpdate, 'u', 'u.project_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->where('p.company_id', $companyId)
            ->whereIn('p.status', ['accepted', 'in_progress'])
            ->whereRaw('COALESCE(u.last_update_at, p.updated_at) < ?', [now()->subDays(ReportPeriod::STALLED_DAYS)])
            ->count();

        $attention = [
            [
                'label' => 'Requests waiting for your offer',
                'hint' => 'Send a price before another company does.',
                'count' => Quote::query()
                    ->visibleToCompany($companyId)
                    ->where('status', 'pending')
                    ->whereDoesntHave('companyQuotations', fn($q) => $q->where('company_id', $companyId))
                    ->count(),
                'url' => route('company.quote-requests.index', ['status' => 'pending']),
                'tone' => 'orange',
            ],
            [
                'label' => 'Projects not started',
                'hint' => 'The customer accepted your offer. Start the installation.',
                'count' => (clone $projects)->where('status', 'accepted')->count(),
                'url' => route('company.projects.index', ['status' => 'accepted']),
                'tone' => 'blue',
            ],
            [
                'label' => 'Ready for customer handover',
                'hint' => 'All phases done. Confirm the handover to close it.',
                'count' => (clone $projects)->where('status', 'in_progress')->where('progress_percent', '>=', 99)->count(),
                'url' => route('company.projects.index', ['status' => 'in_progress']),
                'tone' => 'green',
            ],
            [
                'label' => 'Projects idle for ' . ReportPeriod::STALLED_DAYS . '+ days',
                'hint' => 'Post an update so the customer knows what is happening.',
                'count' => $idleProjects,
                'url' => route('company.projects.index'),
                'tone' => 'red',
            ],
        ];

        return [
            'attention' => $attention,
            'attentionTotal' => array_sum(array_column($attention, 'count')),
            'today' => [
                ['label' => 'New requests', 'count' => Quote::query()->visibleToCompany($companyId)->where('created_at', '>=', $today)->count()],
                ['label' => 'Offers sent', 'count' => CompanyQuotation::query()->where('company_id', $companyId)->where('created_at', '>=', $today)->count()],
                ['label' => 'Project updates', 'count' => DB::table('tbl_project_updates as pu')
                    ->join('tbl_projects as p', 'p.id', '=', 'pu.project_id')
                    ->where('p.company_id', $companyId)
                    ->whereNull('pu.deleted_at')
                    ->where('pu.created_at', '>=', $today)
                    ->count()],
            ],
            'accountStatus' => $company?->status,
            'isVerified' => (bool) $company?->is_verified,
            'publicProfileUrl' => $company?->slug ? route('company-profile.show', ['slug' => $company->slug]) : null,
        ];
    }

    //Dashboard End

    //Reports Start

    /**
     * Reports page for the logged-in company only.
     * "Period" figures use records created in the range; "current" figures are the state right now.
     */
    public function getCompanyReportsData(array $filters): array
    {
        $companyId = $this->authenticatedCompanyId();
        $period = $this->resolveCompanyReportPeriod($companyId, $filters);
        $from = $period['from'];
        $to = $period['to'];

        return [
            'period' => $period,
            'kpis' => $this->companyReportKpis($companyId, $period),
            'trend' => $this->companyReportTrend($companyId, $from, $to, $period['days']),
            'requests' => $this->companyReportRequests($companyId, $from, $to),
            'offers' => $this->companyReportOffers($companyId, $from, $to),
            'projects' => $this->companyReportProjects($companyId, $from, $to),
            'customers' => $this->companyReportCustomers($companyId, $from, $to),
            'products' => $this->companyReportProducts($companyId),
            'notifications' => $this->companyReportNotifications($companyId, $from, $to),
        ];
    }

    /**
     * Rows for a CSV download of one report section (this company's data only).
     */
    public function getCompanyReportExport(string $section, array $filters): array
    {
        $companyId = $this->authenticatedCompanyId();
        $period = $this->resolveCompanyReportPeriod($companyId, $filters);
        $from = $period['from'];
        $to = $period['to'];
        $suffix = $from->format('Y-m-d') . '_to_' . $to->format('Y-m-d');
        $ref = fn($id) => '#Q-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);

        if ($section === 'offers') {
            $rows = DB::table('tbl_company_quotations as o')
                ->join('tbl_quote_requests as q', 'q.id', '=', 'o.quote_id')
                ->leftJoin('tbl_customers as c', 'c.id', '=', 'q.customer_id')
                ->whereNull('o.deleted_at')
                ->where('o.company_id', $companyId)
                ->whereBetween('o.created_at', [$from, $to])
                ->orderBy('o.created_at')
                ->get(['q.id as quote_id', 'q.created_at as received_at', 'o.created_at as offered_at', 'c.full_name', 'c.username', 'c.city', 'q.quote_name', 'o.price', 'o.currency', 'o.status', 'q.status as request_status']);

            return [
                'filename' => "my-offers_{$suffix}.csv",
                'headers' => ['Request', 'Request received', 'Offer sent', 'Customer', 'City', 'Quote name', 'Price', 'Currency', 'Offer status', 'Request status'],
                'rows' => $rows->map(fn($r) => [
                    $ref($r->quote_id), $r->received_at, $r->offered_at, $r->full_name ?: $r->username, $r->city,
                    $r->quote_name, $r->price, $r->currency, $r->status, $r->request_status,
                ])->all(),
            ];
        }

        if ($section === 'projects') {
            $rows = DB::table('tbl_projects as p')
                ->leftJoin('tbl_customers as c', 'c.id', '=', 'p.customer_id')
                ->leftJoin('tbl_company_quotations as o', 'o.id', '=', 'p.company_quotation_id')
                ->leftJoin('tbl_quote_requests as q', 'q.id', '=', 'p.quote_id')
                ->whereNull('p.deleted_at')
                ->where('p.company_id', $companyId)
                ->whereBetween('p.created_at', [$from, $to])
                ->orderBy('p.created_at')
                ->get(['p.id', 'p.quote_id', 'c.full_name', 'c.username', 'c.city', 'q.quote_name', 'p.status', 'p.progress_percent', 'o.price', 'o.currency', 'p.accepted_at', 'p.started_at', 'p.completed_at']);

            return [
                'filename' => "my-projects_{$suffix}.csv",
                'headers' => ['Project', 'Request', 'Customer', 'City', 'Quote name', 'Status', 'Progress %', 'Price', 'Currency', 'Accepted', 'Started', 'Completed'],
                'rows' => $rows->map(fn($r) => [
                    $r->id, $ref($r->quote_id), $r->full_name ?: $r->username, $r->city, $r->quote_name, $r->status,
                    $r->progress_percent, $r->price, $r->currency, $r->accepted_at, $r->started_at, $r->completed_at,
                ])->all(),
            ];
        }

        // Customers who selected this company's offer in the period (no contact details).
        $rows = $this->companyWonCustomersQuery($companyId, $from, $to)
            ->orderByDesc('value_sdg')
            ->get();

        return [
            'filename' => "my-customers_{$suffix}.csv",
            'headers' => ['Customer', 'City', 'Offers won', 'Value won (SDG)', 'First win', 'Last win'],
            'rows' => $rows->map(fn($r) => [
                $r->full_name ?: $r->username, $r->city, (int) $r->wins, $r->value_sdg, $r->first_win_at, $r->last_win_at,
            ])->all(),
        ];
    }

    private function resolveCompanyReportPeriod(int $companyId, array $filters): array
    {
        return ReportPeriod::resolve($filters, function () use ($companyId) {
            return SolarCompany::query()->whereKey($companyId)->value('created_at') ?? now()->startOfYear();
        });
    }

    /** Requests this company can see (assigned to it, open to all, or already offered on). */
    private function companyVisibleQuotes(int $companyId)
    {
        return Quote::query()->visibleToCompany($companyId);
    }

    private function companyOffers(int $companyId)
    {
        return DB::table('tbl_company_quotations')
            ->whereNull('deleted_at')
            ->where('company_id', $companyId);
    }

    private function companyProjects(int $companyId)
    {
        return DB::table('tbl_projects')
            ->whereNull('deleted_at')
            ->where('company_id', $companyId);
    }

    private function companyReportKpis(int $companyId, array $period): array
    {
        $counters = [
            'requests_received' => ['Requests received', fn($a, $b) => $this->companyVisibleQuotes($companyId)->whereBetween('created_at', [$a, $b])->count()],
            'offers_sent' => ['Offers sent', fn($a, $b) => $this->companyOffers($companyId)->whereBetween('created_at', [$a, $b])->count()],
            'offers_won' => ['Offers won', fn($a, $b) => $this->companyOffers($companyId)->where('status', 'selected')->whereBetween('created_at', [$a, $b])->count()],
            'value_won' => ['Value won (SDG)', fn($a, $b) => (float) $this->companyOffers($companyId)->where('status', 'selected')->where('currency', 'SDG')->whereBetween('created_at', [$a, $b])->sum('price')],
            'customers_won' => ['Customers won', fn($a, $b) => $this->companyOffers($companyId)->where('status', 'selected')->whereBetween('created_at', [$a, $b])->distinct()->count('customer_id')],
            'projects_started' => ['Projects started', fn($a, $b) => $this->companyProjects($companyId)->whereBetween('created_at', [$a, $b])->count()],
            'projects_completed' => ['Projects completed', fn($a, $b) => $this->companyProjects($companyId)->whereNotNull('completed_at')->whereBetween('completed_at', [$a, $b])->count()],
        ];

        $kpis = [];

        foreach ($counters as $key => [$label, $count]) {
            $current = $count($period['from'], $period['to']);
            $previous = $period['previous_from'] ? $count($period['previous_from'], $period['previous_to']) : null;

            $kpis[$key] = [
                'label' => $label,
                'value' => $current,
                'previous' => $previous,
                'change' => ReportPeriod::change($current, $previous),
            ];
        }

        // Response rate: requests received in the period that this company sent an offer on.
        $received = $kpis['requests_received']['value'];
        $answered = $this->companyVisibleQuotes($companyId)
            ->whereBetween('created_at', [$period['from'], $period['to']])
            ->whereHas('companyQuotations', fn($q) => $q->where('company_id', $companyId))
            ->count();

        $kpis['response_rate'] = [
            'label' => 'Response rate',
            'value' => $received > 0 ? (int) round($answered / $received * 100) : 0,
            'suffix' => '%',
            'previous' => null,
            'change' => null,
            'hint' => "Offered on {$answered} of {$received} requests",
        ];

        // Win rate: selected ÷ decided offers sent in the period.
        $selected = $this->companyOffers($companyId)->where('status', 'selected')->whereBetween('created_at', [$period['from'], $period['to']])->count();
        $lost = $this->companyOffers($companyId)->where('status', 'not_selected')->whereBetween('created_at', [$period['from'], $period['to']])->count();

        $kpis['win_rate'] = [
            'label' => 'Win rate',
            'value' => ($selected + $lost) > 0 ? (int) round($selected / ($selected + $lost) * 100) : 0,
            'suffix' => '%',
            'previous' => null,
            'change' => null,
            'hint' => "{$selected} won, {$lost} not selected",
        ];

        return $kpis;
    }

    private function companyReportTrend(int $companyId, Carbon $from, Carbon $to, int $days): array
    {
        $format = ReportPeriod::bucketFormat($days);
        $bucket = "DATE_FORMAT(created_at, '{$format}')";

        $series = [
            'requests' => $this->companyVisibleQuotes($companyId)->whereBetween('created_at', [$from, $to])
                ->selectRaw("{$bucket} as bucket, COUNT(*) as total")->groupBy('bucket')->pluck('total', 'bucket'),
            'offers' => $this->companyOffers($companyId)->whereBetween('created_at', [$from, $to])
                ->selectRaw("{$bucket} as bucket, COUNT(*) as total")->groupBy('bucket')->pluck('total', 'bucket'),
            'won' => $this->companyOffers($companyId)->where('status', 'selected')->whereBetween('created_at', [$from, $to])
                ->selectRaw("{$bucket} as bucket, COUNT(*) as total")->groupBy('bucket')->pluck('total', 'bucket'),
        ];

        $trend = ReportPeriod::trend($from, $to, $days, $series);
        $trend['max'] = max(1, collect($trend['buckets'])->max(fn($b) => max($b['requests'], $b['offers'])));

        return $trend;
    }

    private function companyReportRequests(int $companyId, Carbon $from, Carbon $to): array
    {
        $inPeriod = fn() => $this->companyVisibleQuotes($companyId)->whereBetween('tbl_quote_requests.created_at', [$from, $to]);

        $responseMinutes = DB::table('tbl_company_quotations as o')
            ->join('tbl_quote_requests as q', 'q.id', '=', 'o.quote_id')
            ->whereNull('o.deleted_at')
            ->where('o.company_id', $companyId)
            ->whereBetween('o.created_at', [$from, $to])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, q.created_at, o.created_at)) as minutes')
            ->value('minutes');

        // Open requests still waiting for this company's offer (any date).
        $awaiting = $this->companyVisibleQuotes($companyId)
            ->where('status', 'pending')
            ->whereDoesntHave('companyQuotations', fn($q) => $q->where('company_id', $companyId));

        return [
            'by_status' => $inPeriod()->selectRaw('status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'by_service' => $inPeriod()
                ->selectRaw("COALESCE(NULLIF(TRIM(quote_name), ''), 'Not specified') as k, COUNT(*) as n")
                ->groupBy('k')->orderByDesc('n')->limit(8)->pluck('n', 'k')->all(),
            'by_city' => $inPeriod()
                ->leftJoin('tbl_customers as c', 'c.id', '=', 'tbl_quote_requests.customer_id')
                ->selectRaw("COALESCE(NULLIF(TRIM(c.city), ''), 'Unknown') as k, COUNT(*) as n")
                ->groupBy('k')->orderByDesc('n')->limit(8)->pluck('n', 'k')->all(),
            'assigned_to_you' => $inPeriod()->where('company_id', $companyId)->count(),
            'open_marketplace' => $inPeriod()->whereNull('company_id')->count(),
            'avg_response_hours' => $responseMinutes !== null ? round($responseMinutes / 60, 1) : null,
            'awaiting_count' => (clone $awaiting)->count(),
            'awaiting' => $awaiting
                ->with('customer:id,full_name,username,city')
                ->withCount('companyQuotations')
                ->orderBy('created_at')
                ->limit(10)
                ->get()
                ->map(fn($quote) => [
                    'id' => $quote->id,
                    'customer' => $quote->customer?->full_name ?: ($quote->customer?->username ?: '—'),
                    'city' => $quote->customer?->city ?: '—',
                    'service' => $quote->quote_name ?: '—',
                    'competing_offers' => (int) $quote->company_quotations_count,
                    'created_at' => $quote->created_at,
                    'age_hours' => (int) $quote->created_at->diffInHours(now()),
                ])->all(),
        ];
    }

    private function companyReportOffers(int $companyId, Carbon $from, Carbon $to): array
    {
        $inPeriod = fn() => $this->companyOffers($companyId)->whereBetween('created_at', [$from, $to]);

        $byStatus = $inPeriod()->selectRaw('status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all();

        // Market comparison (SDG): other companies' offers on the same requests you offered on.
        $yourQuoteIds = $inPeriod()->pluck('quote_id');

        $yourAvg = $inPeriod()->where('currency', 'SDG')->avg('price');
        $marketAvg = DB::table('tbl_company_quotations')
            ->whereNull('deleted_at')
            ->whereIn('quote_id', $yourQuoteIds)
            ->where('company_id', '!=', $companyId)
            ->where('currency', 'SDG')
            ->avg('price');

        // Requests you lost: your price vs the price the customer chose.
        $lostQuoteIds = $inPeriod()->where('status', 'not_selected')->pluck('quote_id');
        $lostYourAvg = $inPeriod()->where('status', 'not_selected')->where('currency', 'SDG')->avg('price');
        $lostWinningAvg = DB::table('tbl_company_quotations')
            ->whereNull('deleted_at')
            ->whereIn('quote_id', $lostQuoteIds)
            ->where('company_id', '!=', $companyId)
            ->where('status', 'selected')
            ->where('currency', 'SDG')
            ->avg('price');

        $percentDiff = fn($yours, $theirs) => ($yours !== null && $theirs !== null && (float) $theirs > 0)
            ? (int) round(((float) $yours - (float) $theirs) / (float) $theirs * 100)
            : null;

        return [
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'prices' => $inPeriod()
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
            'market' => [
                'your_avg' => $yourAvg !== null ? (float) $yourAvg : null,
                'market_avg' => $marketAvg !== null ? (float) $marketAvg : null,
                'diff' => $percentDiff($yourAvg, $marketAvg),
                'lost_count' => $lostQuoteIds->count(),
                'lost_your_avg' => $lostYourAvg !== null ? (float) $lostYourAvg : null,
                'lost_winning_avg' => $lostWinningAvg !== null ? (float) $lostWinningAvg : null,
                'lost_diff' => $percentDiff($lostYourAvg, $lostWinningAvg),
            ],
        ];
    }

    private function companyReportProjects(int $companyId, Carbon $from, Carbon $to): array
    {
        $completedInPeriod = $this->companyProjects($companyId)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$from, $to]);

        $avgCompletionHours = (clone $completedInPeriod)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, COALESCE(accepted_at, created_at), completed_at)) as hours')
            ->value('hours');

        $lastUpdate = DB::table('tbl_project_updates')
            ->whereNull('deleted_at')
            ->selectRaw('project_id, MAX(created_at) as last_update_at')
            ->groupBy('project_id');

        // Active projects, least progress first, with days since the last update.
        $active = DB::table('tbl_projects as p')
            ->leftJoinSub($lastUpdate, 'u', 'u.project_id', '=', 'p.id')
            ->leftJoin('tbl_customers as c', 'c.id', '=', 'p.customer_id')
            ->leftJoin('tbl_quote_requests as q', 'q.id', '=', 'p.quote_id')
            ->whereNull('p.deleted_at')
            ->where('p.company_id', $companyId)
            ->whereIn('p.status', ['accepted', 'in_progress'])
            ->orderBy('p.progress_percent')
            ->orderBy('p.created_at')
            ->limit(15)
            ->get(['p.id', 'p.quote_id', 'p.status', 'p.progress_percent', 'p.created_at', 'c.full_name', 'c.username', 'q.quote_name', DB::raw('COALESCE(u.last_update_at, p.updated_at) as last_activity_at')])
            ->map(function ($r) {
                $idleDays = (int) Carbon::parse($r->last_activity_at)->diffInDays(now());

                return [
                    'id' => $r->id,
                    'quote_id' => $r->quote_id,
                    'status' => $r->status,
                    'progress' => (int) $r->progress_percent,
                    'customer' => $r->full_name ?: ($r->username ?: '—'),
                    'service' => $r->quote_name ?: '—',
                    'idle_days' => $idleDays,
                    'stalled' => $idleDays >= ReportPeriod::STALLED_DAYS,
                ];
            })->all();

        return [
            'by_status' => $this->companyProjects($companyId)->selectRaw('status as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'total' => $this->companyProjects($companyId)->count(),
            'started_in_period' => $this->companyProjects($companyId)->whereBetween('created_at', [$from, $to])->count(),
            'completed_in_period' => (clone $completedInPeriod)->count(),
            'avg_completion_days' => $avgCompletionHours !== null ? round($avgCompletionHours / 24, 1) : null,
            'avg_active_progress' => (int) round((float) $this->companyProjects($companyId)->whereIn('status', ['accepted', 'in_progress'])->avg('progress_percent')),
            'milestones' => DB::table('tbl_project_milestones as m')
                ->join('tbl_projects as p', 'p.id', '=', 'm.project_id')
                ->whereNull('m.deleted_at')
                ->whereNull('p.deleted_at')
                ->where('p.company_id', $companyId)
                ->selectRaw('m.status as k, COUNT(*) as n')
                ->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'updates_in_period' => DB::table('tbl_project_updates as pu')
                ->join('tbl_projects as p', 'p.id', '=', 'pu.project_id')
                ->whereNull('pu.deleted_at')
                ->where('p.company_id', $companyId)
                ->whereBetween('pu.created_at', [$from, $to])
                ->count(),
            'active' => $active,
            'stalled_count' => collect($active)->where('stalled', true)->count(),
        ];
    }

    /** Customers who selected this company's offer, with totals for the range. */
    private function companyWonCustomersQuery(int $companyId, Carbon $from, Carbon $to)
    {
        $wins = DB::table('tbl_company_quotations')
            ->whereNull('deleted_at')
            ->where('company_id', $companyId)
            ->where('status', 'selected')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('customer_id, COUNT(*) as wins')
            ->selectRaw("SUM(CASE WHEN currency = 'SDG' THEN price ELSE 0 END) as value_sdg")
            ->selectRaw('MIN(created_at) as first_win_at, MAX(created_at) as last_win_at')
            ->groupBy('customer_id');

        return DB::table('tbl_customers as c')
            ->joinSub($wins, 'w', 'w.customer_id', '=', 'c.id')
            ->select(['c.id', 'c.full_name', 'c.username', 'c.city', 'w.wins', 'w.value_sdg', 'w.first_win_at', 'w.last_win_at']);
    }

    private function companyReportCustomers(int $companyId, Carbon $from, Carbon $to): array
    {
        // All-time: customers with a project from this company.
        $served = $this->companyProjects($companyId)
            ->selectRaw('customer_id, COUNT(*) as projects')
            ->groupBy('customer_id');

        $servedRows = DB::table('tbl_customers as c')
            ->joinSub($served, 's', 's.customer_id', '=', 'c.id')
            ->get(['c.id', 'c.city', 's.projects']);

        $avgProjectValue = DB::table('tbl_projects as p')
            ->join('tbl_company_quotations as o', 'o.id', '=', 'p.company_quotation_id')
            ->whereNull('p.deleted_at')
            ->where('p.company_id', $companyId)
            ->where('o.currency', 'SDG')
            ->avg('o.price');

        $topInPeriod = $this->companyWonCustomersQuery($companyId, $from, $to)
            ->orderByDesc('w.value_sdg')
            ->orderByDesc('w.wins')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'name' => $r->full_name ?: $r->username,
                'city' => $r->city ?: '—',
                'wins' => (int) $r->wins,
                'value_sdg' => (float) $r->value_sdg,
                'last_win_at' => Carbon::parse($r->last_win_at),
            ])->all();

        return [
            'served_total' => $servedRows->count(),
            'repeat' => $servedRows->where('projects', '>=', 2)->count(),
            'won_in_period' => $this->companyOffers($companyId)->where('status', 'selected')->whereBetween('created_at', [$from, $to])->distinct()->count('customer_id'),
            'avg_project_value' => $avgProjectValue !== null ? (float) $avgProjectValue : null,
            'by_city' => $servedRows
                ->countBy(fn($r) => trim((string) $r->city) !== '' ? $r->city : 'Unknown')
                ->sortDesc()
                ->take(8)
                ->all(),
            'top' => $topInPeriod,
        ];
    }

    private function companyReportProducts(int $companyId): array
    {
        $products = fn() => DB::table('tbl_products')
            ->whereNull('tbl_products.deleted_at')
            ->where('tbl_products.company_id', $companyId);

        return [
            'total' => $products()->count(),
            'active' => $products()->where('is_active', true)->count(),
            'by_type' => $products()->selectRaw('type as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'by_supplier' => $products()
                ->join('tbl_suppliers as s', 's.id', '=', 'tbl_products.supplier_id')
                ->whereNull('s.deleted_at')
                ->selectRaw('s.name as k, COUNT(*) as n')
                ->groupBy('k')->orderByDesc('n')->limit(8)->pluck('n', 'k')->all(),
        ];
    }

    private function companyReportNotifications(int $companyId, Carbon $from, Carbon $to): array
    {
        $sent = fn() => $this->companyNotificationsQuery()
            ->where('status', 'published')
            ->whereBetween('published_at', [$from, $to]);

        $deliveries = DB::table('tbl_customer_notifications as cn')
            ->whereIn('cn.notification_id', $sent()->select('id'))
            ->whereNull('cn.deleted_at')
            ->selectRaw('COUNT(*) as recipients, SUM(cn.read_at IS NOT NULL) as read_count')
            ->first();

        $recipients = (int) $deliveries->recipients;

        return [
            'sent' => $sent()->count(),
            'by_type' => $sent()->selectRaw('type as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->pluck('n', 'k')->all(),
            'recipients' => $recipients,
            'read' => (int) $deliveries->read_count,
            'read_rate' => $recipients > 0 ? (int) round($deliveries->read_count / $recipients * 100) : null,
        ];
    }

    //Reports End


    //Quote Requests Start

    private function authenticatedCompanyId(): int
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return (int) $company->getAuthIdentifier();
    }

    public function getQuoteRequests(array $filters = []): LengthAwarePaginator
    {
        $companyId = $this->authenticatedCompanyId();

        $query = Quote::query()
            ->with([
                'customer',
                'company',
                'companyQuotations' => function ($quotationQuery) use ($companyId) {
                    // Load this company's own offers, including withdrawn offers.
                    $quotationQuery
                        ->where('company_id', $companyId)
                        ->select([
                            'id',
                            'quote_id',
                            'company_id',
                            'price',
                            'currency',
                            'status',
                            'created_at',
                            'updated_at',
                        ]);
                },
                // Project row only when this company won the request.
                'project' => fn($projectQuery) => $projectQuery
                    ->where('company_id', $companyId)
                    ->select(['id', 'quote_id', 'company_id', 'status', 'progress_percent']),
            ])
            // Total offers from all companies (identities are never shown).
            ->withCount(['companyQuotations as total_offers_count'])
            // Requests assigned to this company, available to all, or already offered on.
            ->visibleToCompany($companyId)
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
                        ->orWhere('daily_load', 'like', "%{$search}%")
                        ->orWhere('peak_load', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            // tbl_customers has full_name/username, not name.
                            $customerQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $query->paginate(10)->withQueryString();
    }

    public function getQuoteRequestMetrics(): array
    {
        $companyId = $this->authenticatedCompanyId();

        $query = Quote::query()->visibleToCompany($companyId);

        return [
            'total' => (clone $query)->count(),

            'pending' => (clone $query)
                ->where('status', 'pending')
                ->count(),

            'in_progress' => (clone $query)
                ->where('status', 'accepted')
                ->count(),

            'completed' => (clone $query)
                ->where('status', 'completed')
                ->count(),

            // Open requests this company has not sent an offer on yet.
            'awaiting_offer' => (clone $query)
                ->where('status', 'pending')
                ->whereDoesntHave('companyQuotations', fn($q) => $q->where('company_id', $companyId))
                ->count(),

            'won' => CompanyQuotation::query()
                ->where('company_id', $companyId)
                ->where('status', 'selected')
                ->count(),
        ];
    }

    public function getQuoteRequestById(int $id): ?Quote
    {
        $companyId = $this->authenticatedCompanyId();

        return Quote::query()
            ->with(['customer', 'company'])
            ->where('id', $id)
            ->visibleToCompany($companyId)
            ->first();
    }

    public function getQuoteRequestCount(): int
    {
        $companyId = $this->authenticatedCompanyId();

        return Quote::query()
            ->where('company_id', $companyId)
            ->count();
    }

    public function submitQuoteOffer(int $quoteId, array $data): CompanyQuotation
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        $companyId = (int) $company->getAuthIdentifier();

        [$quotation, $type] = DB::transaction(function () use ($quoteId, $companyId, $data) {
            // Lock the request to prevent offer submission while it is being closed.
            $quote = Quote::query()
                ->whereKey($quoteId)
                ->lockForUpdate()
                ->firstOrFail();

            // Offers can only be created or edited while the request is pending.
            if ($quote->status !== 'pending') {
                throw ValidationException::withMessages([
                    'price' => 'This request is no longer accepting or updating offers.',
                ]);
            }

            // Find this company's existing offer for the request.
            $quotation = CompanyQuotation::query()
                ->where('quote_id', $quote->id)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            // Allow marketplace requests, requests assigned to this company,
            // or requests this company has already offered on.
            if (
                !$quotation &&
                $quote->company_id !== null &&
                (int) $quote->company_id !== $companyId
            ) {
                abort(403, 'This quote request is not available to your company.');
            }

            if ($quotation) {
                // Once selected by the customer, the offer is permanently locked.
                if ($quotation->status === 'selected') {
                    throw ValidationException::withMessages([
                        'price' => 'The customer has selected your offer. You can no longer edit it.',
                    ]);
                }

                // Only submitted offers may be edited.
                if ($quotation->status !== 'submitted') {
                    throw ValidationException::withMessages([
                        'price' => 'This offer cannot be edited because it is no longer active.',
                    ]);
                }

                // Update the existing row; do not insert another quotation.
                $quotation->customer_id = $quote->customer_id;
                $quotation->price = $data['price'];
                $quotation->currency = $data['currency'];
                $quotation->message = $data['message'] ?? null;
                $quotation->ar_message = $data['ar_message'] ?? null;
                $quotation->save();

                return [$quotation->refresh(), 'offer_updated'];
            }

            // No existing offer: create the first offer for this company/request.
            $quotation = new CompanyQuotation();
            $quotation->quote_id = $quote->id;
            $quotation->customer_id = $quote->customer_id;
            $quotation->company_id = $companyId;
            $quotation->price = $data['price'];
            $quotation->currency = $data['currency'];
            $quotation->message = $data['message'] ?? null;
            $quotation->ar_message = $data['ar_message'] ?? null;
            $quotation->status = 'submitted';
            $quotation->save();

            return [$quotation, 'offer_new'];
        });

        // Push only after commit, so a rolled-back offer never notifies.
        $this->helper->notifyCustomerAboutOffer($quotation, $type);

        return $quotation;
    }

    public function getCompanyQuotationForQuote(int $quoteId): ?CompanyQuotation
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return CompanyQuotation::query()
            ->where('quote_id', $quoteId)
            ->where('company_id', $company->getAuthIdentifier())
            // Include withdrawn offers so the company sees its final offer status.
            ->first();
    }

    public function getCompetitorOfferPrices(int $quoteId)
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return CompanyQuotation::query()
            ->where('quote_id', $quoteId)
            ->where('company_id', '!=', $company->getAuthIdentifier())
            ->where('status', 'submitted')
            ->orderBy('currency')
            ->orderBy('price')
            ->get(['price', 'currency']);
    }

    //Quote Requests End


    //Notifications Start

    private function companyNotificationsQuery(): Builder
    {
        return Notification::query()
            ->where('sender_type', 'company')
            ->where('sender_id', $this->authenticatedCompanyId());
    }

    /**
     * Soft-deletes one of this company's notifications and its customer copies,
     * so it also disappears from the app and from unread counts.
     */
    public function deleteNotification(int $id): array
    {
        // Scoped query: a company can only delete what it sent.
        /** @var Notification|null $notification */
        $notification = $this->companyNotificationsQuery()->find($id);

        if (!$notification) {
            return ['success' => false, 'message' => 'Notification not found.'];
        }

        try {
            CustomerNotification::query()->where('notification_id', $notification->id)->delete();
            $notification->delete();
        } catch (Throwable $e) {
            report($e);

            return ['success' => false, 'message' => 'Unable to delete the notification. Please try again.'];
        }

        return ['success' => true, 'message' => 'Notification deleted. Customers no longer see it in the app.'];
    }

    public function publishNotification(array $data): Notification
    {
        $startedAt = microtime(true);

        // Never accept the sender from the submitted form.
        $data['sender_type'] = 'company';
        $data['sender_id'] = $this->authenticatedCompanyId();

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

                    $customerIds = $audienceType === 'all'
                        ? Customer::query()
                            ->whereHas('deviceTokens')
                            ->pluck('id')
                            ->map(fn($id) => (int) $id)
                            ->all()
                        : array_values(array_unique(
                            array_map('intval', $data['customer_ids'] ?? [])
                        ));

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
                            'customer_ids' => 'Select at least one customer.',
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
                    ]);

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

        foreach ($devices as $device) {
            $lang = strtolower($device->lang ?? 'en') === 'ar'
                ? 'ar'
                : 'en';

            $title = $lang === 'ar'
                ? ($notification->title_ar ?: $notification->title_en)
                : $notification->title_en;

            $body = $lang === 'ar'
                ? ($notification->message_ar ?: $notification->message_en)
                : $notification->message_en;

            // Never log the actual FCM token.
            $tokenFingerprint = substr(
                hash('sha256', $device->fcm_token),
                0,
                12
            );

            Log::info('SunXFinder notification: sending push', [
                'notification_id' => $notification->id,
                'device_id' => $device->id,
                'customer_id' => $device->customer_id,
                'platform' => $device->platform ?? null,
                'lang' => $lang,
                'token_fingerprint' => $tokenFingerprint,
            ]);

            try {
                $message = Helper::withNotificationSound(CloudMessage::new())
                    ->toToken($device->fcm_token)
                    ->withNotification(
                        FcmNotification::create($title, $body)
                    )
                    ->withData([
                        'notification_id' => (string) $notification->id,
                        'type' => (string) $notification->type,
                        'lang' => $lang,
                    ]);

                $sendResult = $messaging->send($message);

                $successCount++;

                Log::info('SunXFinder notification: Firebase accepted push', [
                    'notification_id' => $notification->id,
                    'device_id' => $device->id,
                    'customer_id' => $device->customer_id,
                    'token_fingerprint' => $tokenFingerprint,
                    'firebase_result_type' => get_debug_type($sendResult),
                ]);
            } catch (Throwable $e) {
                $failureCount++;

                Log::error('SunXFinder notification: push failed', [
                    'notification_id' => $notification->id,
                    'device_id' => $device->id,
                    'customer_id' => $device->customer_id,
                    'token_fingerprint' => $tokenFingerprint,
                    'exception_class' => get_class($e),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
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
        $query = $this->companyNotificationsQuery()
            ->withCount('recipients')
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

        $publishedCount = $this->companyNotificationsQuery()->where('status', 'published')->count();
        $draftCount = $this->companyNotificationsQuery()->where('status', 'draft')->count();

        $recipientCount = CustomerNotification::query()
            ->whereIn('notification_id', $this->companyNotificationsQuery()->select('id'))
            ->count();

        return [
            'notifications' => $notifications,
            'publishedCount' => $publishedCount,
            'draftCount' => $draftCount,
            'recipientCount' => $recipientCount,
        ];
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


    public function getNotificationDetails(int $id): array
    {
        // Other senders' notifications are reported as not found.
        $notification = $this->companyNotificationsQuery()
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

    /** This company's visible ratings, newest first. Hidden ones were removed by admin. */
    /**
     * "Company Customers": customers who chose this company, i.e. accepted one of its
     * offers (which creates a project). One row per customer, most recent first.
     */
    public function getCompanyCustomers(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('tbl_projects as p')
            ->join('tbl_customers as c', 'c.id', '=', 'p.customer_id')
            ->where('p.company_id', $this->authenticatedCompanyId())
            ->whereNull('p.deleted_at')
            ->when($search !== '', fn($q) => $q->where(fn($w) => $w
                ->where('c.full_name', 'like', "%{$search}%")
                ->orWhere('c.username', 'like', "%{$search}%")
                ->orWhere('c.phone', 'like', "%{$search}%")
                ->orWhere('c.city', 'like', "%{$search}%")))
            ->groupBy('c.id', 'c.full_name', 'c.username', 'c.phone', 'c.email', 'c.city')
            ->select('c.id', 'c.full_name', 'c.username', 'c.phone', 'c.email', 'c.city')
            ->selectRaw('COUNT(*) as projects')
            ->selectRaw("SUM(p.status IN ('accepted', 'in_progress')) as active_projects")
            ->selectRaw("SUM(p.status = 'completed') as completed_projects")
            ->selectRaw('MIN(p.created_at) as first_project_at')
            ->selectRaw('MAX(p.created_at) as last_project_at')
            ->selectRaw('MAX(p.id) as last_project_id')
            ->orderByDesc('last_project_at')
            ->paginate(15)
            ->withQueryString();
    }

    /** Ids of every customer who accepted one of this company's offers. */
    public function getCompanyCustomerIds(): array
    {
        return DB::table('tbl_projects')
            ->where('company_id', $this->authenticatedCompanyId())
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('customer_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    /** Totals for the Company Customers page. */
    public function getCompanyCustomerSummary(): array
    {
        $projects = DB::table('tbl_projects')
            ->where('company_id', $this->authenticatedCompanyId())
            ->whereNull('deleted_at');

        return [
            'customers' => (clone $projects)->distinct()->count('customer_id'),
            'repeat' => (clone $projects)->select('customer_id')->groupBy('customer_id')
                ->havingRaw('COUNT(*) > 1')->get()->count(),
            'active' => (clone $projects)->whereIn('status', ['accepted', 'in_progress'])->distinct()->count('customer_id'),
            'this_month' => (clone $projects)->where('created_at', '>=', now()->startOfMonth())->distinct()->count('customer_id'),
        ];
    }

    public function getCompanyRatings(array $filters = []): LengthAwarePaginator
    {
        return Rating::query()
            ->visible()
            ->where('company_id', $this->authenticatedCompanyId())
            ->with([
                'customer:id,full_name,username,city',
                'quote:id,quote_name',
                'answers' => fn($q) => $q->with('question')->orderBy('question_id'),
            ])
            ->when(!empty($filters['stars']), fn($q) => $q->where('stars', (int) $filters['stars']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    /** Average stars, star distribution and per-question averages for this company. */
    public function getCompanyRatingSummary(): array
    {
        $companyId = $this->authenticatedCompanyId();

        $visible = Rating::query()->visible()->where('company_id', $companyId);

        $distribution = (clone $visible)
            ->selectRaw('stars, COUNT(*) as n')
            ->groupBy('stars')
            ->pluck('n', 'stars');

        $total = (int) $distribution->sum();
        $average = (clone $visible)->avg('stars');

        // Average answer per question, over this company's visible ratings.
        $questions = DB::table('tbl_rating_answers as a')
            ->join('tbl_ratings as r', 'r.id', '=', 'a.rating_id')
            ->join('tbl_rating_questions as q', 'q.id', '=', 'a.question_id')
            ->where('r.company_id', $companyId)
            ->where('r.is_visible', true)
            ->groupBy('q.id', 'q.title_en', 'q.question_en', 'q.sort_order')
            ->orderBy('q.sort_order')
            ->orderBy('q.id')
            ->get([
                'q.id',
                'q.title_en',
                'q.question_en',
                DB::raw('AVG(a.value) as avg_value'),
                DB::raw('COUNT(*) as answers'),
            ])
            ->map(fn($row) => [
                'title' => $row->title_en,
                'question' => $row->question_en,
                'average' => round((float) $row->avg_value, 1),
                'answers' => (int) $row->answers,
            ])
            ->all();

        return [
            'total' => $total,
            'average' => $total > 0 ? round((float) $average, 1) : null,
            'this_month' => (clone $visible)->where('created_at', '>=', now()->startOfMonth())->count(),
            'with_comment' => (clone $visible)->whereNotNull('comment')->count(),
            // stars => count, 5 down to 1, zeros included.
            'distribution' => collect(range(Rating::MAX_STARS, Rating::MIN_STARS))
                ->mapWithKeys(fn($stars) => [$stars => (int) ($distribution[$stars] ?? 0)])
                ->all(),
            'questions' => $questions,
        ];
    }

    //Ratings End
}
