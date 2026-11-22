<?php

namespace App\Repositories\Company;

use App\Helpers\Helper;
use App\Support\AdTracker;
use App\Support\BusinessStats;
use App\Models\AppSlider;
use App\Models\AdSale;
use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\PackageOffer;
use App\Models\PackageOfferResponse;
use App\Models\Product;
use App\Repositories\PackageOfferRepository;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Rating;
use App\Models\Setting;
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
use App\Support\OfferTerms;


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
                    'message' => __('company/messages.profile.not_found'),
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
                'message' => __('company/messages.profile.retrieve_failed'),
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
                    'message' => __('company/messages.profile.not_found'),
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
                    'message' => __('company/messages.profile.update_failed'),
                ];
            }

            return [
                'success' => true,
                'message' => __('company/messages.profile.updated'),
                'company' => $company->fresh(),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => __('company/messages.profile.update_error'),
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

        return DB::transaction(function () use ($product) {
            $this->helper->archiveItem($product, 'product', null, 'website');

            return (bool) $product->delete();
        });
    }

    //Company profile END


    /**
     * True only on the company's first sign-in: the panel then opens the "how to use the system" journey.
     * Saved quietly so it does not touch updated_at or the activity log.
     */
    public function markJourneySeen(SolarCompany $company): bool
    {
        if ($company->journey_seen_at) {
            return false;
        }

        $company->timestamps = false;
        $company->forceFill(['journey_seen_at' => now()])->saveQuietly();
        $company->timestamps = true;

        return true;
    }

    //Password Recovery Start

    public function companyEmailExists(string $email): bool
    {
        return SolarCompany::query()
            ->where('email', $email)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->exists();
    }

    public function sendPasswordResetOtp(string $email, string $locale = 'en'): array
    {
        $company = SolarCompany::query()
            ->where('email', $email)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->first();

        if (!$company) {
            return ['success' => false, 'message' => __('company/messages.auth.no_account')];
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

        $company = SolarCompany::query()
            ->where('email', $email)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->first();

        if (!$company) {
            return ['success' => false, 'message' => __('company/messages.auth.no_account')];
        }

        // The "hashed" cast hashes the new password; a new remember token signs out other devices.
        $company->password = $password;
        $company->setRememberToken(Str::random(60));
        $company->save();

        $this->otpRepository->consume($check['otp']);

        return ['success' => true, 'message' => __('company/messages.auth.reset_done')];
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
            'handover_pending' => (int) ($projectCounts[Project::HANDOVER_PENDING] ?? 0),
            'completed' => (int) ($projectCounts['completed'] ?? 0),
        ];
        $projects['active'] = $projects['accepted'] + $projects['in_progress'] + $projects['handover_pending'];
        $projects['average_progress'] = (int) round((float) Project::query()
            ->where('company_id', $companyId)
            ->whereIn('status', Project::ACTIVE_STATUSES)
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
                'label' => $date->translatedFormat('D'),
                'date' => $date->translatedFormat('d M'),
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
            'rating' => BusinessStats::ratingOverall($companyId),
            'customersServed' => CompanyQuotation::query()->where('tbl_company_quotations.company_id', $companyId)->where('tbl_company_quotations.status', 'selected')
                ->join('tbl_quote_requests as q', 'q.id', '=', 'tbl_company_quotations.quote_id')->distinct()->count('q.customer_id'),
            // Ad results for the last 30 days, only when Advertising is open to this company.
            'ads' => Setting::companyAdsEnabled($companyId) ? BusinessStats::advertising(now()->subDays(29)->startOfDay(), now(), $companyId) : null,
            // Package offer results for the last 30 days.
            'packageOffers' => BusinessStats::packageOffers(now()->subDays(29)->startOfDay(), now(), $companyId),
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
            ...(Setting::companyAdsEnabled($companyId) ? [[
                'label' => __('company/messages.ads.topbar_ending', ['days' => AdSale::ENDING_SOON_DAYS]),
                'hint' => __('company/messages.ads.topbar_ending_hint'),
                'count' => AdSale::query()->where('company_id', $companyId)->endingSoon()->count(),
                'url' => route('company.ads.index'),
                'tone' => 'blue',
            ]] : []),
            [
                'label' => __('company/messages.topbar.waiting_offer'),
                'hint' => __('company/messages.topbar.waiting_offer_hint'),
                'count' => Quote::query()
                    ->visibleToCompany($companyId)
                    ->where('status', 'pending')
                    ->whereDoesntHave('companyQuotations', fn($q) => $q->where('company_id', $companyId))
                    ->count(),
                'url' => route('company.quote-requests.index', ['status' => 'pending']),
                'tone' => 'orange',
            ],
            [
                'label' => __('company/messages.topbar.not_started'),
                'hint' => __('company/messages.topbar.not_started_hint'),
                'count' => (clone $projects)->where('status', 'accepted')->count(),
                'url' => route('company.projects.index', ['status' => 'accepted']),
                'tone' => 'blue',
            ],
            [
                'label' => __('company/messages.topbar.ready_handover'),
                'hint' => __('company/messages.topbar.ready_handover_hint'),
                'count' => (clone $projects)->where('status', 'in_progress')->where('progress_percent', '>=', 99)->count(),
                'url' => route('company.projects.index', ['status' => 'in_progress']),
                'tone' => 'green',
            ],
            [
                'label' => __('company/messages.topbar.idle', ['days' => ReportPeriod::STALLED_DAYS]),
                'hint' => __('company/messages.topbar.idle_hint'),
                'count' => $idleProjects,
                'url' => route('company.projects.index'),
                'tone' => 'red',
            ],
        ];

        return [
            'attention' => $attention,
            'attentionTotal' => array_sum(array_column($attention, 'count')),
            'today' => [
                ['label' => __('company/messages.topbar.new_requests'), 'count' => Quote::query()->visibleToCompany($companyId)->where('created_at', '>=', $today)->count()],
                ['label' => __('company/messages.topbar.offers_sent'), 'count' => CompanyQuotation::query()->where('company_id', $companyId)->where('created_at', '>=', $today)->count()],
                ['label' => __('company/messages.topbar.project_updates'), 'count' => DB::table('tbl_project_updates as pu')
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
            'ratings' => BusinessStats::ratings($from, $to, $companyId),
            'packageOffers' => BusinessStats::packageOffers($from, $to, $companyId),
            'advertising' => Setting::companyAdsEnabled($companyId) ? BusinessStats::advertising($from, $to, $companyId) : null,
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

        if ($section === 'package_offers') {
            $answers = [
                PackageOfferResponse::ACCEPTED => __('company/messages.reports.pkg_accepted'),
                PackageOfferResponse::DECLINED => __('company/messages.reports.pkg_declined'),
            ];
            $lang = app()->getLocale();

            return [
                'filename' => "my-package-offers_{$suffix}.csv",
                'headers' => array_map(fn($key) => __('company/messages.' . $key), ['reports.pkg_offer', 'package_offers.max_load', 'reports.price', 'reports.currency', 'reports.pkg_answer', 'reports.pkg_answered_at', 'common.customer', 'common.city', 'common.request', 'common.project', 'common.status']),
                'rows' => BusinessStats::packageOfferAnswers($from, $to, $companyId)->map(fn($r) => [
                    $lang === 'ar' && filled($r->title_ar) ? $r->title_ar : $r->title_en, $r->max_load_kw, $r->price, $r->currency,
                    $answers[$r->status] ?? $r->status, $r->responded_at, $r->full_name ?: $r->username, $r->city,
                    $r->quote_id ? $ref($r->quote_id) : '', $r->project_id, $r->project_status,
                ])->all(),
            ];
        }

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
                'headers' => array_map(fn($key) => __('company/messages.' . $key), ['common.request', 'reports.request_date', 'reports.offer_sent_date', 'common.customer', 'common.city', 'common.quote_name', 'reports.price', 'reports.currency', 'reports.offer_status_label', 'reports.request_status_label']),
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
                'headers' => array_map(fn($key) => __('company/messages.' . $key), ['common.project', 'common.request', 'common.customer', 'common.city', 'common.quote_name', 'common.status', 'reports.progress_percent', 'reports.price', 'reports.currency', 'reports.accepted_date', 'reports.started_date', 'reports.completed_date']),
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
            'headers' => array_map(fn($key) => __('company/messages.' . $key), ['common.customer', 'common.city', 'reports.offers_won', 'reports.value_won_sdg', 'reports.first_win', 'reports.last_win_csv']),
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
            'requests_received' => [__('company/messages.reports.requests_received'), fn($a, $b) => $this->companyVisibleQuotes($companyId)->whereBetween('created_at', [$a, $b])->count()],
            'offers_sent' => [__('company/messages.reports.offers_sent'), fn($a, $b) => $this->companyOffers($companyId)->whereBetween('created_at', [$a, $b])->count()],
            'offers_won' => [__('company/messages.reports.offers_won'), fn($a, $b) => $this->companyOffers($companyId)->where('status', 'selected')->whereBetween('created_at', [$a, $b])->count()],
            'value_won' => [__('company/messages.reports.value_won_sdg'), fn($a, $b) => (float) $this->companyOffers($companyId)->where('status', 'selected')->where('currency', 'SDG')->whereBetween('created_at', [$a, $b])->sum('price')],
            'customers_won' => [__('company/messages.reports.customers_won'), fn($a, $b) => $this->companyOffers($companyId)->where('status', 'selected')->whereBetween('created_at', [$a, $b])->distinct()->count('customer_id')],
            'projects_started' => [__('company/messages.reports.projects_started'), fn($a, $b) => $this->companyProjects($companyId)->whereBetween('created_at', [$a, $b])->count()],
            'projects_completed' => [__('company/messages.reports.projects_completed'), fn($a, $b) => $this->companyProjects($companyId)->whereNotNull('completed_at')->whereBetween('completed_at', [$a, $b])->count()],
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
            'label' => __('company/messages.reports.response_rate'),
            'value' => $received > 0 ? (int) round($answered / $received * 100) : 0,
            'suffix' => '%',
            'previous' => null,
            'change' => null,
            'hint' => __('company/messages.reports.kpi_response_hint', ['answered' => $answered, 'received' => $received]),
        ];

        // Win rate: selected ÷ decided offers sent in the period.
        $selected = $this->companyOffers($companyId)->where('status', 'selected')->whereBetween('created_at', [$period['from'], $period['to']])->count();
        $lost = $this->companyOffers($companyId)->where('status', 'not_selected')->whereBetween('created_at', [$period['from'], $period['to']])->count();

        $kpis['win_rate'] = [
            'label' => __('company/messages.reports.win_rate'),
            'value' => ($selected + $lost) > 0 ? (int) round($selected / ($selected + $lost) * 100) : 0,
            'suffix' => '%',
            'previous' => null,
            'change' => null,
            'hint' => __('company/messages.reports.kpi_win_hint', ['selected' => $selected, 'lost' => $lost]),
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
            ->whereIn('p.status', Project::ACTIVE_STATUSES)
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
            'avg_active_progress' => (int) round((float) $this->companyProjects($companyId)->whereIn('status', Project::ACTIVE_STATUSES)->avg('progress_percent')),
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

    /** Warranty and terms of this company's most recently saved offer that had any. */
    public function getLastOfferTerms(): ?array
    {
        return CompanyQuotation::query()
            ->where('company_id', $this->authenticatedCompanyId())
            ->whereNotNull('terms')
            ->latest('updated_at')
            ->latest('id')
            ->first(['id', 'terms', 'updated_at'])
            ?->terms;
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
        /** @var SolarCompany|null $company */
        $company = Auth::guard('company')->user();

        abort_unless(
            $company instanceof SolarCompany && $company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        $companyId = (int) $company->getAuthIdentifier();

        [$quotation, $type] = DB::transaction(function () use ($quoteId, $companyId, $company, $data) {
            // Lock the request to prevent offer submission while it is being closed.
            $quote = Quote::query()
                ->whereKey($quoteId)
                ->lockForUpdate()
                ->firstOrFail();

            // Offers can only be created or edited while the request is pending.
            if ($quote->status !== 'pending') {
                throw ValidationException::withMessages([
                    'price' => __('company/messages.quote.not_accepting_updates'),
                ]);
            }

            $quote->loadMissing('customer');
            $invoiceData = !empty($data['include_invoice'])
                ? $this->makeQuoteInvoiceData($quote, $company, $data)
                : null;
            $offerPrice = $invoiceData
                ? $invoiceData['balance_due']
                : $data['price'];

            // SunXFinder commission at the company's rate now, read fresh so an admin change
            // applies right away. Kept on the offer, so later rate changes do not rewrite it.
            // A commission the admin set for this offer (percent or fixed) is kept instead.
            $companyRate = round((float) SolarCompany::query()->whereKey($companyId)->value('commission_rate'), 2);

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
                        'price' => __('company/messages.quote.selected_locked'),
                    ]);
                }

                // Only submitted offers may be edited.
                if ($quotation->status !== 'submitted') {
                    throw ValidationException::withMessages([
                        'price' => __('company/messages.quote.inactive_offer'),
                    ]);
                }

                // Update the existing row; do not insert another quotation.
                $quotation->customer_id = $quote->customer_id;
                $quotation->price = $offerPrice;
                $quotation->currency = $data['currency'];
                $quotation->applyCommission($companyRate);
                $quotation->message = $data['message'] ?? null;
                $quotation->ar_message = $data['ar_message'] ?? null;
                $quotation->terms = OfferTerms::normalize($data['terms'] ?? null);
                $quotation->save();

                $this->saveOfferInvoice($quotation, $invoiceData);

                return [$quotation->refresh(), 'offer_updated'];
            }

            // No existing offer: create the first offer for this company/request.
            $quotation = new CompanyQuotation();
            $quotation->quote_id = $quote->id;
            $quotation->customer_id = $quote->customer_id;
            $quotation->company_id = $companyId;
            $quotation->price = $offerPrice;
            $quotation->currency = $data['currency'];
            $quotation->commission_type = CompanyQuotation::COMMISSION_COMPANY;
            $quotation->applyCommission($companyRate);
            $quotation->message = $data['message'] ?? null;
            $quotation->ar_message = $data['ar_message'] ?? null;
            $quotation->terms = OfferTerms::normalize($data['terms'] ?? null);
            $quotation->status = 'submitted';
            $quotation->save();

            $this->saveOfferInvoice($quotation, $invoiceData);

            return [$quotation, 'offer_received'];
        });

        // Push only after commit, so a rolled-back offer never notifies.
        $this->helper->notifyCustomerAboutOffer($quotation, $type);

        return $quotation;
    }

    /** Invoice lines with product picking, the same way package offers build theirs. */
    private function normalizeOfferItems(array $items, int $companyId, string $errorKey = 'invoice_items'): array
    {
        return app(PackageOfferRepository::class)->normalizeItems($items, $companyId, $errorKey);
    }

    private function makeQuoteInvoiceData(Quote $quote, SolarCompany $company, array $data): array
    {
        $items = $this->normalizeOfferItems($data['invoice_items'] ?? [], (int) $company->getAuthIdentifier());

        $subtotal = round(array_sum(array_column($items, 'total')), 2);
        $discount = min($subtotal, round((float) ($data['invoice_discount'] ?? 0), 2));
        $taxRate = round((float) ($data['invoice_tax_rate'] ?? 0), 2);
        $tax = round(max(0, $subtotal - $discount) * $taxRate / 100, 2);
        $shipping = round((float) ($data['invoice_shipping'] ?? 0), 2);
        $balanceDue = round($subtotal - $discount + $tax + $shipping, 2);

        if ($balanceDue < 1000 || max($subtotal, $balanceDue) > 9999999999999) {
            throw ValidationException::withMessages([
                'invoice_items' => __('company/messages.quote.balance_limit'),
            ]);
        }

        return [
            'quote_id' => $quote->id,
            'company_id' => $company->getAuthIdentifier(),
            'customer_id' => $quote->customer_id,
            'invoice_number' => trim((string) $data['invoice_number']),
            'invoice_date' => $data['invoice_date'],
            // The payment terms picked in "Warranty and terms", else the default.
            'payment_terms' => in_array($data['terms']['payment_terms'] ?? null, OfferTerms::PAYMENT_TERMS, true)
                ? __('company/messages.offer_terms.payment_' . $data['terms']['payment_terms'], [], 'en')
                : 'Due upon acceptance',
            'payment_instructions' => trim((string) ($data['invoice_notes'] ?? '')) ?: null,
            'currency' => $data['currency'],
            'company_details' => [
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->city,
            ],
            'customer_details' => [
                'name' => $quote->customer?->full_name ?: $quote->customer?->username,
                'email' => $quote->customer?->email,
                'phone' => $quote->customer?->phone,
                'address' => $quote->customer?->address,
            ],
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'shipping' => $shipping,
            'balance_due' => $balanceDue,
        ];
    }

    /**
     * Keep the offer's invoice in step with the submitted form. Each offer has one
     * invoice row: turning the invoice off soft-deletes it, turning it on again restores it.
     */
    private function saveOfferInvoice(CompanyQuotation $quotation, ?array $invoiceData): void
    {
        $invoice = Invoice::withTrashed()
            ->where('company_quotation_id', $quotation->id)
            ->first();

        if (!$invoiceData) {
            if ($invoice && !$invoice->trashed()) {
                $invoice->delete();
            }

            return;
        }

        // Numbers stay reserved by soft-deleted invoices, matching the unique index.
        $numberTaken = Invoice::withTrashed()
            ->where('company_id', $invoiceData['company_id'])
            ->where('invoice_number', $invoiceData['invoice_number'])
            ->where('company_quotation_id', '!=', $quotation->id)
            ->exists();

        if ($numberTaken) {
            throw ValidationException::withMessages([
                'invoice_number' => __('company/messages.quote.invoice_number_used'),
            ]);
        }

        $invoice ??= new Invoice(['company_quotation_id' => $quotation->id]);

        if ($invoice->trashed()) {
            $invoice->restore();
        }

        $invoice->fill($invoiceData)->save();
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

    public function getQuoteInvoice(int $quoteId): ?Invoice
    {
        if (!$this->getQuoteRequestById($quoteId)) {
            return null;
        }

        return $this->getCompanyQuotationForQuote($quoteId)?->invoice;
    }

    public function getCompanyInvoices(array $filters = []): LengthAwarePaginator
    {
        $companyId = $this->authenticatedCompanyId();
        $query = Invoice::query()
            ->with('quotation.quote')
            ->where('company_id', $companyId);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('invoice_number', 'like', '%' . $search . '%')
                    ->orWhere('customer_details->name', 'like', '%' . $search . '%');

                if (ctype_digit($search)) {
                    $query->orWhere('quote_id', (int) $search);
                }
            });
        }

        return $query
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function deleteCompanyInvoice(int $invoiceId): array
    {
        $companyId = $this->authenticatedCompanyId();

        return DB::transaction(function () use ($invoiceId, $companyId) {
            $invoiceReference = Invoice::query()
                ->where('company_id', $companyId)
                ->find($invoiceId);

            if (!$invoiceReference) {
                return ['success' => false, 'message' => __('company/messages.invoices.not_found')];
            }

            // Match the acceptance flow lock order: quote first, then offer.
            $quote = Quote::query()
                ->whereKey($invoiceReference->quote_id)
                ->lockForUpdate()
                ->first();

            $quotation = CompanyQuotation::query()
                ->whereKey($invoiceReference->company_quotation_id)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            $invoice = Invoice::query()
                ->where('company_id', $companyId)
                ->whereKey($invoiceId)
                ->lockForUpdate()
                ->first();

            if (!$invoice || !$quote || !$quotation) {
                return ['success' => false, 'message' => __('company/messages.invoices.not_found')];
            }

            $acceptedByCustomer = $quotation->status === 'selected'
                || (in_array($quote->status, ['accepted', 'completed'], true)
                    && (int) $quote->company_id === $companyId);

            if ($acceptedByCustomer) {
                return [
                    'success' => false,
                    'message' => __('company/messages.invoices.locked'),
                ];
            }

            $invoice->delete();

            return ['success' => true, 'message' => __('company/messages.invoices.deleted')];
        });
    }

    public function getProjectForQuote(int $quoteId): ?Project
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return Project::query()
            ->where('quote_id', $quoteId)
            ->where('company_id', $company->getAuthIdentifier())
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

    //Package Offers Start

    /** This company's active products for the item pickers, grouped by type in the views. */
    public function getCompanyProductOptions(): \Illuminate\Support\Collection
    {
        return app(PackageOfferRepository::class)->productOptions([$this->authenticatedCompanyId()]);
    }

    /**
     * Customers a company may send its package offers to: only its own customers (who
     * accepted one of its offers) that the app can reach. Unlike other notifications,
     * admin opening "all customers" does not apply to offers.
     */
    /**
     * Every app customer a package offer can be pushed to, own customers first.
     * Only the company's own customers carry a name and city; the rest are "Customer-{id}".
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, label: string, city: ?string, is_own: bool}>
     */
    public function getOfferRecipients(): \Illuminate\Support\Collection
    {
        $companyId = $this->authenticatedCompanyId();
        $ownIds = array_flip($this->ownReachableCustomers($companyId)->pluck('id')->all());

        return $this->reachableCustomers()
            ->select('id', 'full_name', 'username', 'city')
            ->orderBy('id')
            ->get()
            ->map(fn(Customer $customer) => $this->offerCustomerView($customer->id, isset($ownIds[$customer->id]) ? $customer : null))
            ->sortBy(fn($row) => [$row['is_own'] ? 0 : 1, $row['is_own'] ? mb_strtolower($row['label']) : $row['id']])
            ->values();
    }

    /** What the company may see of a customer: the name for its own customers, a number otherwise. */
    private function offerCustomerView(int $id, ?Customer $ownCustomer): array
    {
        return [
            'id' => $id,
            'label' => $ownCustomer
                ? ($ownCustomer->full_name ?: $ownCustomer->username ?: __('company/messages.common.customer_number', ['id' => $id]))
                : __('company/messages.package_offers.customer_masked', ['id' => $id]),
            'city' => $ownCustomer?->city,
            'is_own' => $ownCustomer !== null,
        ];
    }

    /** Active app customers with a working device, whichever company they work with. */
    private function reachableCustomers(): Builder
    {
        return Customer::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('deviceTokens', fn($tokens) => $tokens->active());
    }

    private function ownReachableCustomers(int $companyId): Builder
    {
        return $this->onlyCompanyCustomers(
            Customer::query()->where('status', 'active')->where('is_active', true),
            $companyId
        )->whereHas('deviceTokens', fn($tokens) => $tokens->active());
    }

    public function getPackageOffers(array $filters = []): LengthAwarePaginator
    {
        $companyId = $this->authenticatedCompanyId();

        $offers = PackageOffer::query()
            ->where('company_id', $companyId)
            ->withCount([
                'responses as accepted_count' => fn($q) => $q->where('status', PackageOfferResponse::ACCEPTED),
                'responses as declined_count' => fn($q) => $q->where('status', PackageOfferResponse::DECLINED),
            ])
            ->when(($filters['status'] ?? null) === 'active', fn($q) => $q
                ->where('status', PackageOffer::STATUS_ACTIVE)
                ->where(fn($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->when(($filters['status'] ?? null) === 'ended', fn($q) => $q->where(fn($w) => $w
                ->where('status', PackageOffer::STATUS_CLOSED)
                ->orWhere('expires_at', '<=', now())))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = trim($filters['search']);
                $q->where(fn($w) => $w->where('title_en', 'like', "%{$search}%")->orWhere('title_ar', 'like', "%{$search}%"));
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $sentCounts = $this->packageOfferRecipientCounts($offers->getCollection()->pluck('id')->all());
        $offers->getCollection()->each(fn(PackageOffer $offer) => $offer->setAttribute('sent_count', $sentCounts[$offer->id] ?? 0));

        return $offers;
    }

    /** Distinct customers each offer was pushed to. */
    private function packageOfferRecipientCounts(array $offerIds): array
    {
        if (!$offerIds) {
            return [];
        }

        return CustomerNotification::query()
            ->join('tbl_notifications as n', 'n.id', '=', 'tbl_customer_notifications.notification_id')
            ->whereIn('n.package_offer_id', $offerIds)
            ->whereNull('n.deleted_at')
            ->groupBy('n.package_offer_id')
            ->selectRaw('n.package_offer_id as offer_id, COUNT(DISTINCT tbl_customer_notifications.customer_id) as total')
            ->pluck('total', 'offer_id')
            ->map(fn($total) => (int) $total)
            ->all();
    }

    public function findPackageOffer(int $id): PackageOffer
    {
        return PackageOffer::query()
            ->where('company_id', $this->authenticatedCompanyId())
            ->findOrFail($id);
    }

    /** One offer with who answered it. Contact details stay hidden, as on quote requests. */
    public function getPackageOfferDetails(int $id): array
    {
        $offer = $this->findPackageOffer($id);
        $companyId = (int) $offer->company_id;

        $responses = PackageOfferResponse::query()
            ->where('package_offer_id', $offer->id)
            ->with([
                'customer:id,full_name,username,city',
                'quote' => fn($q) => $q->select('id', 'status', 'company_id', 'peak_load', 'daily_load', 'created_at')
                    ->with([
                        'companyQuotations' => fn($o) => $o->where('company_id', $companyId)->select('id', 'quote_id', 'company_id', 'status', 'price', 'currency'),
                        'project' => fn($p) => $p->where('company_id', $companyId)->select('id', 'quote_id', 'company_id', 'status'),
                    ]),
            ])
            // Accepted first: those need the company's review.
            ->orderByRaw("status = 'accepted' DESC")
            ->latest('responded_at')
            ->paginate(20)
            ->withQueryString();

        // Names only for this company's own customers (anyone who accepted is one).
        $ownIds = array_flip($this->onlyCompanyCustomers(
            Customer::query()->whereIn('id', $responses->getCollection()->pluck('customer_id')->filter()->all()),
            $companyId
        )->pluck('id')->all());

        $responses->getCollection()->each(function (PackageOfferResponse $response) use ($ownIds) {
            $own = isset($ownIds[$response->customer_id]) ? $response->customer : null;
            $response->setAttribute('customer_view', $this->offerCustomerView((int) $response->customer_id, $own));
            $response->setRelation('customer', null);
        });

        return [
            'offer' => $offer,
            'responses' => $responses,
            'sentCount' => $this->packageOfferRecipientCounts([$offer->id])[$offer->id] ?? 0,
            'acceptedCount' => $offer->responses()->where('status', PackageOfferResponse::ACCEPTED)->count(),
            'declinedCount' => $offer->responses()->where('status', PackageOfferResponse::DECLINED)->count(),
        ];
    }

    public function savePackageOffer(array $data, ?int $id = null, ?\Illuminate\Http\UploadedFile $image = null): PackageOffer
    {
        $companyId = $this->authenticatedCompanyId();

        return app(PackageOfferRepository::class)->save(
            $companyId,
            $data,
            $id ? $this->findPackageOffer($id) : null,
            $image,
            'company',
            $companyId
        );
    }

    /** Stops new acceptances. Requests already sent stay with the company. */
    public function closePackageOffer(int $id): PackageOffer
    {
        $offer = $this->findPackageOffer($id);
        $offer->update(['status' => PackageOffer::STATUS_CLOSED]);

        return $offer;
    }

    /** Only an offer that was never sent can be deleted; a sent one is closed instead. */
    public function deletePackageOffer(int $id): array
    {
        $offer = $this->findPackageOffer($id);

        if ($offer->isLocked()) {
            return ['success' => false, 'message' => __('company/messages.package_offers.cannot_delete')];
        }

        $offer->delete();

        return ['success' => true, 'message' => __('company/messages.package_offers.deleted')];
    }

    /** Pushes the offer, with its image, to the chosen customers: the company's own customers only. */
    public function sendPackageOffer(int $id, array $data): Notification
    {
        $offer = $this->findPackageOffer($id);

        if (!$offer->isOpen()) {
            throw ValidationException::withMessages([
                'customer_ids' => __('company/messages.package_offers.not_open'),
            ]);
        }

        $price = number_format((float) $offer->price) . ' ' . $offer->currency;
        $load = $offer->maxLoadLabel();

        // Called "Special offer" / "عرض خاص" in the app, so the push says the same.
        $notification = $this->publishNotification([
            'type' => 'offer',
            'audience_type' => 'selected',
            'customer_ids' => $data['customer_ids'],
            'title_en' => Str::limit('Special offer: ' . $offer->title_en, 150, ''),
            'title_ar' => Str::limit('عرض خاص: ' . ($offer->title_ar ?: $offer->title_en), 150, ''),
            'message_en' => filled($data['message_en'] ?? null)
                ? trim($data['message_en'])
                : "A special offer: a ready solar system for loads up to {$load} kW, for {$price}. Open the offer, add your devices and accept it.",
            'message_ar' => filled($data['message_ar'] ?? null)
                ? trim($data['message_ar'])
                : "عرض خاص: منظومة طاقة شمسية جاهزة لأحمال حتى {$load} كيلوواط، بسعر {$price}. افتح العرض وأضف أجهزتك واقبله.",
            'all_app_customers' => true,
        ] + Notification::offerCardFields($offer));

        if (!$offer->sent_at) {
            $offer->update(['sent_at' => now()]);
        }

        return $notification;
    }

    /** The offer behind a request, when the customer accepted one of this company's offers. */
    public function getPackageOfferForQuote(Quote $quote): ?PackageOffer
    {
        if (!$quote->package_offer_id) {
            return null;
        }

        return PackageOffer::withTrashed()
            ->where('company_id', $this->authenticatedCompanyId())
            ->find($quote->package_offer_id);
    }

    //Package Offers End


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
            DB::transaction(function () use ($notification) {
                $this->helper->archiveItem($notification, 'notification', null, 'website');

                $customerNotifications = CustomerNotification::query()
                    ->where('notification_id', $notification->id)
                    ->get();

                foreach ($customerNotifications as $customerNotification) {
                    $this->helper->archiveItem(
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

            return ['success' => false, 'message' => __('company/messages.notifications.delete_failed')];
        }

        return ['success' => true, 'message' => __('company/messages.notifications.deleted')];
    }

    /**
     * $automatic marks updates about the customer's own request or project, such as a
     * new offer. They skip the audience rules, which only limit what a company sends itself.
     */
    public function publishNotification(array $data, bool $automatic = false): Notification
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
                ->transaction(function () use ($data, $context, $automatic) {
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
                                __('company/messages.notifications.no_broadcast'),
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
                            'customer_ids' => __('company/messages.notifications.select_one'),
                        ]);
                    }

                    $validCustomers = match (true) {
                        $senderType !== 'company' || $automatic => Customer::query(),
                        // Package offers can go to any app customer (names stay hidden in the portal).
                        !empty($data['all_app_customers']) => $this->reachableCustomers(),
                        default => $this->notifiableCustomers((int) $senderId),
                    };

                    $existingIds = $validCustomers
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
                            'customer_ids' => $senderType === 'company'
                                ? __('company/messages.notifications.cannot_notify')
                                : 'One or more selected customers are invalid.',
                        ]);
                    }

                    // Reaching beyond its own customers is a counted, sponsored send.
                    $isSponsored = false;

                    if ($senderType === 'company' && !$automatic) {
                        $outsideIds = array_values(array_diff(
                            $customerIds,
                            array_map('intval', $this->getCompanyCustomerIds())
                        ));

                        if ($outsideIds) {
                            $isSponsored = true;
                            $company = SolarCompany::query()->find($senderId);
                            $limit = $company?->all_customers_monthly_limit;

                            if ($limit !== null && $company->allCustomerSendsThisMonth() >= $limit) {
                                throw ValidationException::withMessages([
                                    'customer_ids' => __('company/messages.ads.limit_reached', ['limit' => $limit]),
                                ]);
                            }

                            $customerIds = array_values(array_diff($customerIds, Customer::query()
                                ->whereIn('id', $outsideIds)
                                ->where('sponsored_push_enabled', false)
                                ->pluck('id')
                                ->map(fn($id) => (int) $id)
                                ->all()));

                            if (empty($customerIds)) {
                                throw ValidationException::withMessages([
                                    'customer_ids' => __('company/messages.ads.all_opted_out'),
                                ]);
                            }
                        }
                    }

                    Log::info('SunXFinder notification: creating notification');

                    // A package offer adds its card (title, price, expiry) and its id.
                    $offerFields = array_intersect_key($data, array_flip([
                        'company_id', 'package_offer_id', 'offer_title_en', 'offer_title_ar',
                        'offer_price', 'offer_old_price', 'offer_currency', 'offer_expires_at',
                        'image_path', 'image_width', 'image_height',
                    ]));

                    $notification = Notification::create([
                        'is_sponsored' => $isSponsored,
                        'sender_type' => $senderType,
                        'sender_id' => $senderId,
                        'type' => $data['type'],
                        'title_en' => $data['title_en'],
                        'title_ar' => $data['title_ar'] ?? null,
                        'message_en' => $data['message_en'],
                        'message_ar' => $data['message_ar'] ?? null,
                        'quote_id' => $data['quote_id'] ?? null,
                        'audience_type' => $audienceType,
                        'status' => 'published',
                        'published_at' => now(),
                    ] + $offerFields);

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
            // Inactive devices have switched notifications off in the app.
            $devices = CustomerDeviceToken::query()
                ->active()
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
                        FcmNotification::create($title, $body, $notification->imageUrl())
                    )
                    ->withData(array_filter([
                        'notification_id' => (string) $notification->id,
                        'type' => (string) $notification->type,
                        'lang' => $lang,
                        'company_id' => $notification->company_id ? (string) $notification->company_id : '',
                        'image_url' => (string) $notification->imageUrl(),
                        // Open the package offer screen on tap.
                        'package_offer_id' => $notification->package_offer_id ? (string) $notification->package_offer_id : '',
                    ], fn($value) => $value !== ''));

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
        $companyId = $this->authenticatedCompanyId();
        $canNotifyAll = $this->canNotifyAllCustomers($companyId);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Customer> $customers */
        $customers = $this->notifiableCustomers($companyId)
            ->select('id', 'full_name', 'username', 'phone', 'email')
            ->orderBy('username')
            ->get();

        // Opening all customers lets a company message them, not read their contact details.
        if ($canNotifyAll) {
            $ownCustomerIds = array_flip($this->getCompanyCustomerIds());

            $customers
                ->reject(fn(Customer $customer) => isset($ownCustomerIds[$customer->id]))
                ->each(function (Customer $customer) {
                    $customer->phone = null;
                    $customer->email = null;
                });
        }

        return [
            'customers' => $customers,
            'canNotifyAll' => $canNotifyAll,
            'sendLimit' => $canNotifyAll ? $this->getSendLimitUsage() : null,
        ];
    }

    /** This month's sends to all customers, and the limit set by admin (null: no limit). */
    public function getSendLimitUsage(): array
    {
        $company = SolarCompany::query()->find($this->authenticatedCompanyId());

        return [
            'open' => (bool) $company?->can_notify_all_customers,
            'limit' => $company?->all_customers_monthly_limit,
            'used' => $company?->allCustomerSendsThisMonth() ?? 0,
        ];
    }

    //Advertising Start

    private const AD_ARTWORK_DIR = 'main/images/ad-requests';

    public function getCompanyAds(): array
    {
        $companyId = $this->authenticatedCompanyId();

        $sales = AdSale::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->paginate(15);

        $sales->getCollection()->each(function (AdSale $sale) {
            $sale->setAttribute('results', AdTracker::saleTotals($sale));
        });

        $month = AdTracker::totals(now()->startOfMonth(), now()->endOfMonth(), $companyId);

        return [
            'sales' => $sales,
            'viewsThisMonth' => array_sum(array_column($month, 'views')),
            'clicksThisMonth' => array_sum(array_column($month, 'clicks')),
            'activeAds' => AdSale::query()->where('company_id', $companyId)->where('status', 'paid')
                ->where(fn($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', today()))->count(),
            'sendLimit' => $this->getSendLimitUsage(),
        ];
    }

    public function requestAd(array $data, ?\Illuminate\Http\UploadedFile $artwork): AdSale
    {
        $start = Carbon::parse($data['starts_on']);

        return AdSale::create([
            'company_id' => $this->authenticatedCompanyId(),
            'product' => $data['product'],
            'platform' => $data['platform'],
            'link_url' => $data['link_url'] ?? null,
            'request_note' => $data['request_note'] ?? null,
            'artwork_path' => $artwork ? Helper::storeOptimizedImage($artwork, self::AD_ARTWORK_DIR, 1600, 88)['path'] : null,
            'currency' => 'SDG',
            'starts_on' => $start,
            'ends_on' => $start->copy()->addWeeks((int) $data['weeks'])->subDay(),
            'status' => 'requested',
        ]);
    }

    /** A company can withdraw a request only until admin has priced it. */
    public function cancelAdRequest(int $id): array
    {
        $sale = AdSale::query()
            ->where('company_id', $this->authenticatedCompanyId())
            ->whereKey($id)
            ->where('status', 'requested')
            ->first();

        if (!$sale) {
            return ['success' => false, 'message' => __('company/messages.ads.cannot_cancel')];
        }

        $sale->update(['status' => 'cancelled']);

        return ['success' => true, 'message' => __('company/messages.ads.cancelled')];
    }

    public function getCompanyAdReport(string $month): array
    {
        $companyId = $this->authenticatedCompanyId();
        $from = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $totals = AdTracker::totals($from, $to, $companyId);

        $sliderNames = AppSlider::withTrashed()->whereIn('id', AdTracker::companyAdIds($companyId)['slider'])->pluck('name', 'id');

        $rows = collect($totals)->map(function (array $stats, string $key) use ($sliderNames) {
            [$type, $id] = explode(':', $key);

            return $stats + [
                'type' => $type,
                'name' => $type === 'slider' ? ($sliderNames[(int) $id] ?? "#{$id}") : null,
                'rate' => AdTracker::rate($stats['views'], $stats['clicks']),
            ];
        })->sortByDesc('views')->values();

        return [
            'month' => $month,
            'from' => $from,
            'rows' => $rows,
            'totalViews' => $rows->sum('views'),
            'totalClicks' => $rows->sum('clicks'),
            'appViews' => $rows->sum('app_views'),
            'websiteViews' => $rows->sum('website_views'),
            'totalRate' => AdTracker::rate($rows->sum('views'), $rows->sum('clicks')),
            'sponsoredSends' => Notification::withTrashed()
                ->where('sender_type', 'company')
                ->where('sender_id', $companyId)
                ->where('is_sponsored', true)
                ->whereBetween('created_at', [$from, $to])
                ->count(),
        ];
    }

    //Advertising End

    private function canNotifyAllCustomers(int $companyId): bool
    {
        return (bool) SolarCompany::query()->whereKey($companyId)->value('can_notify_all_customers');
    }

    /**
     * Customers this company may notify: its own (those who accepted its offer) that the
     * app can reach, or every active customer with a registered device once an admin has
     * opened all customers for it. Devices with notifications switched off are never pushed to.
     */
    private function notifiableCustomers(int $companyId): Builder
    {
        $customers = Customer::query()
            ->where('status', 'active')
            ->where('is_active', true);

        if ($this->canNotifyAllCustomers($companyId)) {
            return $customers->whereHas('deviceTokens');
        }

        return $this->onlyCompanyCustomers($customers, $companyId)
            ->whereHas('deviceTokens', fn($tokens) => $tokens->active());
    }

    /** A customer belongs to a company once they accept its offer (which also opens a project). */
    private function onlyCompanyCustomers(Builder $customers, int $companyId): Builder
    {
        return $customers->where(fn($query) => $query
            ->whereIn('id', CompanyQuotation::query()
                ->where('company_id', $companyId)
                ->where('status', 'selected')
                ->whereNotNull('customer_id')
                ->select('customer_id'))
            ->orWhereIn('id', Project::query()
                ->where('company_id', $companyId)
                ->select('customer_id')));
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
                'message' => __('company/messages.notifications.not_found'),
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
                'customer.deviceTokens:id,customer_id,lang,platform,is_active',
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
            ->selectRaw("SUM(p.status IN ('accepted', 'in_progress', 'handover_pending')) as active_projects")
            ->selectRaw("SUM(p.status = 'completed') as completed_projects")
            ->selectRaw('MIN(p.created_at) as first_project_at')
            ->selectRaw('MAX(p.created_at) as last_project_at')
            ->selectRaw('MAX(p.id) as last_project_id')
            ->orderByDesc('last_project_at')
            ->paginate(15)
            ->withQueryString();
    }

    /** IDs of this company's own customers. */
    public function getCompanyCustomerIds(): array
    {
        return $this->onlyCompanyCustomers(Customer::query(), $this->authenticatedCompanyId())
            ->pluck('id')
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
            'active' => (clone $projects)->whereIn('status', Project::ACTIVE_STATUSES)->distinct()->count('customer_id'),
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
