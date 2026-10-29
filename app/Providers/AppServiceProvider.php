<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Services\Admin\AdminService;
use App\Services\Company\CompanyService;
use App\Services\Sms\LogSmsSender;
use App\Models\ApplianceGroup;
use App\Models\AppVersion;
use App\Models\CompanyCarousel;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Rating;
use App\Models\RatingQuestion;
use App\Models\SolarCompany;
use App\Models\Supplier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Global view helpers (asset_v for cache-busted static asset URLs).
        require_once app_path('helpers.php');

        // SMS provider for OTP codes. Add a case here when a real provider is connected.
        $this->app->bind(SmsSender::class, function () {
            return match (config('services.sms.driver')) {
                default => new LogSmsSender(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // One pagination look for every admin/company list (resources/views/vendor/pagination/sunxfinder.blade.php)
        Paginator::defaultView('vendor.pagination.sunxfinder');

        // Password reset limits, counted per route and IP so they don't share
        // a counter with login or with each other (plain "throttle:x,y" keys by IP only).
        $perRouteAndIp = fn(Request $request) => $request->route()?->getName() . '|' . $request->ip();

        RateLimiter::for('password-email', fn(Request $request) => Limit::perMinute(5)->by($perRouteAndIp($request)));
        RateLimiter::for('password-update', fn(Request $request) => Limit::perMinute(10)->by($perRouteAndIp($request)));
        RateLimiter::for('password-resend', fn(Request $request) => Limit::perMinute(3)->by($perRouteAndIp($request)));

        // Customer app password reset: limited per IP and per email,
        // so one phone cannot flood a single inbox from many IPs either.
        RateLimiter::for('customer-password-forgot', fn(Request $request) => [
            Limit::perMinute(3)->by($perRouteAndIp($request)),
            Limit::perMinute(3)->by('email|' . strtolower((string) $request->input('email'))),
        ]);
        RateLimiter::for('customer-password-verify', fn(Request $request) => Limit::perMinute(10)->by($perRouteAndIp($request)));
        RateLimiter::for('customer-password-reset', fn(Request $request) => Limit::perMinute(10)->by($perRouteAndIp($request)));

        // Existing admin header counts — unchanged.
        View::composer('admin.partials.header', function ($view) {
            $view->with([
                'companyCount' => SolarCompany::count(),
                'customersCount' => Customer::count(),
                'applianceGroupCount' => ApplianceGroup::count(),
                'suppliersCount' => Supplier::count(),
                'productsCount' => Product::count(),
                'carouselCount' => CompanyCarousel::count(),
                'appSliderCount' => \App\Models\AppSlider::count(),
                'quoteCount' => Quote::count(),
                'appVersionCount' => AppVersion::count(),
                'notificationsCount' => Notification::count(),
                'ratingsCount' => Rating::count(),
                'ratingQuestionsCount' => RatingQuestion::count(),
                'failedJobsCount' => \Illuminate\Support\Facades\DB::table('failed_jobs')->count(),
            ]);
        });

        // Top bar alerts and today's activity (admin and company headers).
        View::composer('admin.partials.header', function ($view) {
            $view->with('topbar', Auth::guard('admin')->check()
                ? app(AdminService::class)->getTopbarData()
                : null);
        });

        View::composer('company.partials.header', function ($view) {
            $view->with('topbar', Auth::guard('company')->check()
                ? app(CompanyService::class)->getTopbarData()
                : null);
        });

        // Company sidebar quote count — only quotes assigned to the logged-in company.
        View::composer('company.layouts.app', function ($view) {
            $company = Auth::guard('company')->user();

            $myCompanyQuoteCount = 0;
            $productsCount = 0;
            $myActiveProjectCount = 0;
            $notificationsCount = 0;
            $ratingsCount = 0;
            $companyCustomersCount = 0;

            if ($company) {
                $companyId = $company->getAuthIdentifier();

                $myCompanyQuoteCount = Quote::query()
                    // ->where('status', 'pending')
                    ->visibleToCompany((int) $companyId)
                    ->count();

                $productsCount = Product::query()->where('company_id', $companyId)->count();
                $myActiveProjectCount = Project::query()->where('company_id', $companyId)->count();
                $notificationsCount = Notification::query()->where('sender_id', $companyId)->count();
                $ratingsCount = Rating::query()->visible()->where('company_id', $companyId)->count();
                $companyCustomersCount = Project::query()->where('company_id', $companyId)->distinct()->count('customer_id');
            }


            $view->with([
                'companyCustomersCount' => $companyCustomersCount,
                'myCompanyQuoteCount' => $myCompanyQuoteCount,
                'productsCount' => $productsCount,
                'myActiveProjectCount' => $myActiveProjectCount,
                'notificationsCount' => $notificationsCount,
                'ratingsCount' => $ratingsCount,
            ]);
        });
    }
}
