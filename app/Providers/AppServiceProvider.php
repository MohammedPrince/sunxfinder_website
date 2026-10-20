<?php

namespace App\Providers;

use App\Models\ApplianceGroup;
use App\Models\CompanyCarousel;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\SolarCompany;
use App\Models\Supplier;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // One pagination look for every admin/company list (resources/views/vendor/pagination/sunxfinder.blade.php)
        Paginator::defaultView('vendor.pagination.sunxfinder');

        // Existing admin header counts — unchanged.
        View::composer('admin.partials.header', function ($view) {
            $view->with([
                'companyCount' => SolarCompany::count(),
                'customersCount' => Customer::count(),
                'applianceGroupCount' => ApplianceGroup::count(),
                'suppliersCount' => Supplier::count(),
                'productsCount' => Product::count(),
                'carouselCount' => CompanyCarousel::count(),
                'quoteCount' => Quote::count(),
            ]);
        });

        // Company sidebar quote count — only quotes assigned to the logged-in company.
        View::composer('company.layouts.app', function ($view) {
            $company = Auth::guard('company')->user();

            $myCompanyQuoteCount = 0;
            $productsCount = 0;

            if ($company) {
                $companyId = $company->getAuthIdentifier();

                $myCompanyQuoteCount = Quote::query()
                    // ->where('status', 'pending')
                    ->visibleToCompany((int) $companyId)
                    ->count();

                $productsCount = Product::query()->where('company_id', $companyId)->count();
                $myActiveProjectCount = Project::query()->where('company_id', $companyId)->count();
            }

            

            $view->with([
                'myCompanyQuoteCount' => $myCompanyQuoteCount,
                'productsCount' => $productsCount,
                'myActiveProjectCount'=>$myActiveProjectCount,
            ]);
        });
    }
}
