<?php


use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\MainController as MainController;
use App\Http\Controllers\Company\AuthController as CompanyAuthController;
use App\Http\Controllers\Company\MainController as CompanyMainController;
use App\Http\Controllers\Company\ProjectController;
use App\Http\Controllers\WebsiteController as WebSiteController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;


//Clear All route
Route::get('/clear', function () {

    Artisan::call('optimize');
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');

    return 'Caching, routes, and configuration cleared successfully.';
})->name('clear');

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', [WebSiteController::class, 'index'])->name('home');
Route::get('/companies/{slug}', [WebSiteController::class, 'showCompany'])->name('company-profile.show');

Route::middleware('guest:company')->group(function () {
    Route::get('/create-company-account', [WebSiteController::class, 'registerCompany'])->name('company.register');

    Route::post('/create-company-account', [WebSiteController::class, 'storeCompany'])->name('company.register.store');
});

//Company, Customers Accounts
Route::get('/create-customer-account', [WebSiteController::class, 'registerCustomer'])->name('customer.register');
Route::post('/create-customer-account', [WebSiteController::class, 'storeCustomer'])->name('customer.register.store');

Route::get('/terms', function (\Illuminate\Http\Request $request) {
    $lang = $request->query('lang', app()->getLocale());

    return view(
        $lang === 'ar'
        ? 'visitors.terms-ar'
        : 'visitors.terms'
    );
})->name('terms');

Route::get('/privacy-policy', function (\Illuminate\Http\Request $request) {
    $lang = $request->query('lang', app()->getLocale());

    return view(
        $lang === 'ar'
        ? 'visitors.privacy-policy-ar'
        : 'visitors.privacy-policy'
    );
})->name('privacy-policy');


/*
|--------------------------------------------------------------------------
| Admin authentication
|--------------------------------------------------------------------------
*/


Route::prefix('admin')->name('admin.')->group(function () {

    // Public admin login page
    Route::get('/login', [AdminAuthController::class, 'showLogin',])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login',])->middleware('throttle:10,1')->name('login.submit');

    // /admin -> dashboard when logged in, otherwise the admin login page
    Route::get('/', function () {
        return auth('admin')->check()
            ? redirect()->route('admin.dashboard.page')
            : redirect()->route('admin.login');
    })->name('index');

    // Protected admin pages
    Route::middleware('admin.auth')->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard.page');

        //Mange Companies
        Route::get('/companies', [MainController::class, 'index'])->name('companies.index');

        //Add new company route
        Route::get('/companies/create', [MainController::class, 'createCompany',])->name('companies.create');
        Route::post('/companies', [MainController::class, 'addNewCompany',])->name('companies.store');
        // Update company using the same form
        Route::get('/companies/{id}/edit', [MainController::class, 'editCompany'])->whereNumber('id')->name('companies.edit');
        Route::put('/companies/{id}', [MainController::class, 'updateCompany'])->whereNumber('id')->name('companies.update');
        // Delete company
        Route::delete('/companies/{id}', [MainController::class, 'deleteCompany'])->whereNumber('id')->name('companies.destroy');


        //Manage Customers
        Route::get('/customers', [MainController::class, 'showCustomers',])->name('customers.index');
        Route::get('/customers/create', [MainController::class, 'createCustomer'])->name('customers.create');
        Route::post('/customers', [MainController::class, 'storeCustomer'])->name('customers.store');
        Route::get('/customers/{id}/edit', [MainController::class, 'editCustomer'])->name('customers.edit');
        Route::put('/customers/{id}', [MainController::class, 'updateCustomer'])->name('customers.update');
        Route::delete('/customers/{id}', [MainController::class, 'destroyCustomer'])->name('customers.destroy');

        //Manage Appliance Groups and Items
        Route::get('/calculator-groups', [MainController::class, 'showApplianceGroups'])->name('calculator-groups.index');
        Route::get('/calculator-groups/create', [MainController::class, 'createApplianceGroup'])->name('calculator-groups.create');
        Route::post('/calculator-groups', [MainController::class, 'storeApplianceGroup'])->name('calculator-groups.store');
        Route::get('/calculator-groups/{id}/edit', [MainController::class, 'editApplianceGroup'])->name('calculator-groups.edit');
        Route::put('/calculator-groups/{id}', [MainController::class, 'updateApplianceGroup'])->name('calculator-groups.update');
        Route::delete('/calculator-groups/{id}', [MainController::class, 'destroyApplianceGroup'])->name('calculator-groups.destroy');

        //Supplier and Products Managment Start
        Route::get('/suppliers', [MainController::class, 'showSuppliers'])->name('suppliers.index');
        Route::get('/suppliers/create', [MainController::class, 'createSupplier'])->name('suppliers.create');
        Route::post('/suppliers', [MainController::class, 'storeSupplier'])->name('suppliers.store');
        Route::get('/suppliers/{id}/edit', [MainController::class, 'editSupplier'])->name('suppliers.edit');
        Route::put('/suppliers/{id}', [MainController::class, 'updateSupplier'])->name('suppliers.update');
        Route::delete('/suppliers/{id}', [MainController::class, 'destroySupplier'])->name('suppliers.destroy');

        Route::get('/products', [MainController::class, 'showProducts'])->name('products.index');
        Route::get('/products/create', [MainController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [MainController::class, 'storeProduct'])->name('products.store');
        Route::get('/products/{id}/edit', [MainController::class, 'editProduct'])->name('products.edit');
        Route::put('/products/{id}', [MainController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{id}', [MainController::class, 'destroyProduct'])->name('products.destroy');

        //Company Carousel Managment
        Route::prefix('company-carousel')->name('company-carousel.')->group(function () {
            Route::get('/', [MainController::class, 'companyCarouselIndex'])->name('index');
            Route::get('/create', [MainController::class, 'companyCarouselCreate'])->name('create');
            Route::post('/', [MainController::class, 'companyCarouselStore'])->name('store');
            Route::get('/{id}/edit', [MainController::class, 'companyCarouselEdit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [MainController::class, 'companyCarouselUpdate'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [MainController::class, 'companyCarouselDestroy'])->whereNumber('id')->name('destroy');
        });

        //Quotes Managment
        Route::get('/quote-requests', [MainController::class, 'quoteRequests'])->name('quote-requests.index');
        Route::get('/quote-requests/{id}', [MainController::class, 'quoteRequestDetails'])->whereNumber('id')->name('quote-requests.show');

        //Logout route for admin
        Route::post('/logout', [AdminAuthController::class, 'logout',])->name('logout');


    });
});



/*
|--------------------------------------------------------------------------
| Solar company authentication
|--------------------------------------------------------------------------
*/

Route::prefix('company')->name('company.')->group(function () {

    // /company -> dashboard when logged in, otherwise the company login page
    Route::get('/', function () {
        return auth('company')->check()
            ? redirect()->route('company.dashboard')
            : redirect()->route('company.login');
    })->name('index');

    // Uses the same Blade as the admin login
    Route::get('/login', [CompanyAuthController::class, 'showLogin',])->name('login');
    // Process company credentials
    Route::post('/login', [CompanyAuthController::class, 'login',])->middleware('throttle:10,1')->name('login.submit');

    // Protected company pages
    Route::middleware('company.auth')->group(function () {

        Route::get('/dashboard', function () {
            return view('company.dashboard');
        })->name('dashboard');

        Route::get('/profile', [CompanyMainController::class, 'index'])->name('profile');

        Route::put('/company/profile', [CompanyMainController::class, 'updateProfile'])->name('profile.update');

        Route::get('/products', [CompanyMainController::class, 'showProducts',])->name('products.index');

        Route::get('/products/create', [CompanyMainController::class, 'create',])->name('products.create');

        Route::post('/products', [CompanyMainController::class, 'store',])->name('products.store');

        Route::get('/products/{product}/edit', [CompanyMainController::class, 'edit',])->name('products.edit');

        Route::put('/products/{product}', [CompanyMainController::class, 'update',])->name('products.update');

        Route::delete('/products/{product}', [CompanyMainController::class, 'destroy',])->name('products.destroy');

        //Quotes Managment
        Route::get('/quote-requests', [CompanyMainController::class, 'quoteRequests'])->name('quote-requests.index');
        Route::get('/quote-requests/{id}', [CompanyMainController::class, 'quoteRequestDetails'])->whereNumber('id')->name('quote-requests.show');

        Route::post('/quote-requests/{id}/offer', [CompanyMainController::class, 'submitQuoteOffer'])->whereNumber('id')->name('quote-requests.offer.store');

        //Projects Managment
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{id}', [ProjectController::class, 'show'])->whereNumber('id')->name('projects.show');
        Route::post('/projects/{id}/progress', [ProjectController::class, 'updateProgress',])->whereNumber('id')->name('projects.progress.update');

        Route::post('/logout', [CompanyAuthController::class, 'logout',])->name('logout');
    });
});


/*
|--------------------------------------------------------------------------
| Keep your existing public company profile route below
|--------------------------------------------------------------------------
*/

// Keep your existing Route::get('/companies/{slug}', ...)
// demo profile route here.


/* Demo route for the company profile. Replace this sample array with a database lookup. */
Route::get('/dump-companies/{slug}', function (string $slug) {
    abort_unless($slug === 'sample-solar-company' || $slug === 'tesla-solar' || $slug === 'noor-solar' || $slug === 'deya-solar', 404);

    $company = [
        'name' => 'Sample Solar Company',
        'city' => 'Port Sudan, Sudan',
        'verified' => false,
        'rating' => null,
        'reviews_count' => null,
        'description' => 'A solar service provider helping customers explore solar power, battery storage, and backup solutions. Replace this description with the company’s approved profile information.',
        'years_experience' => null,
        'services' => [
            'Solar panel supply and installation',
            'Residential solar systems',
            'Commercial solar systems',
            'Battery storage and backup',
            'Hybrid and off-grid systems',
            'System maintenance and support',
        ],
        'projects' => [],
        'reviews' => [],
        'email' => null,
        'phone' => null,
        'website' => null,
        'logo' => null,
        'cover' => null,
    ];

    return view('visitors.company-profile', compact('company'));
})->name('dump-companies.show');
