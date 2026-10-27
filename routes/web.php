<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\MainController;
use App\Http\Controllers\Company\AuthController as CompanyAuthController;
use App\Http\Controllers\Company\MainController as CompanyMainController;
use App\Http\Controllers\Company\ProjectController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

// site.maintenance: shows the maintenance page when it is on (Admin → Settings).
Route::controller(WebsiteController::class)->middleware('site.maintenance')->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('/companies/{slug}', 'showCompany')->name('company-profile.show');

    // "Open in App": opens the company in the mobile app, or falls back to the store/website.
    // Also the App Link / Universal Link path the app registers for.
    Route::get('/open/companies/{slug}', 'openCompanyInApp')->name('app.company');

    // Domain verification files for Android App Links and iOS Universal Links
    Route::get('/.well-known/assetlinks.json', 'androidAssetLinks')->name('app.assetlinks');
    Route::get('/.well-known/apple-app-site-association', 'appleAppSiteAssociation')->name('app.aasa');

    // Android APK "Direct download" (404 while the admin has it hidden)
    Route::get('/download/android', 'downloadApk')->middleware('throttle:20,1')->name('app.download');

    // Home page "Contact us" form (emails info@sunxfinder.com)
    Route::post('/contact', 'sendContactMessage')->middleware('throttle:5,1')->name('contact.send');

    // Customer sign-up
    Route::get('/create-customer-account', 'registerCustomer')->name('customer.register');
    Route::post('/create-customer-account', 'storeCustomer')->name('customer.register.store');

    // Company sign-up (hidden from logged-in companies)
    Route::middleware('guest:company')->group(function () {
        Route::get('/create-company-account', 'registerCompany')->name('company.register');
        Route::post('/create-company-account', 'storeCompany')->name('company.register.store');
    });
});

// Legal pages: ?lang=ar shows the Arabic version
Route::get('/terms', function (Request $request) {
    return view($request->query('lang', app()->getLocale()) === 'ar' ? 'visitors.terms-ar' : 'visitors.terms');
})->name('terms');

Route::get('/privacy-policy', function (Request $request) {
    return view($request->query('lang', app()->getLocale()) === 'ar' ? 'visitors.privacy-policy-ar' : 'visitors.privacy-policy');
})->name('privacy-policy');

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    // /admin -> dashboard when logged in, otherwise the login page
    Route::get('/', function () {
        return auth('admin')->check()
            ? redirect()->route('admin.dashboard.page')
            : redirect()->route('admin.login');
    })->name('index');

    // Authentication
    Route::controller(AdminAuthController::class)->group(function () {
        Route::get('/login', 'showLogin')->name('login');
        Route::post('/login', 'login')->middleware('throttle:10,1')->name('login.submit');

        // Forgot password: email check + OTP, then reset page
        Route::post('/forgot-password', 'checkResetEmail')->middleware('throttle:password-email')->name('password.email');
        Route::get('/reset-password', 'showResetPassword')->name('password.reset');
        Route::post('/reset-password', 'resetPassword')->middleware('throttle:password-update')->name('password.update');
        Route::post('/reset-password/resend', 'resendResetOtp')->middleware('throttle:password-resend')->name('password.resend');
    });

    Route::middleware('admin.auth')->group(function () {

        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::controller(MainController::class)->group(function () {

            Route::get('/dashboard', 'dashboard')->name('dashboard.page');

            // Reports (date range via ?preset=30d or ?preset=custom&from=&to=)
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', 'reports')->name('index');
                Route::get('/export/{section}', 'exportReport')->whereIn('section', ['companies', 'quote_requests', 'customers'])->name('export');
            });

            // Solar companies
            Route::prefix('companies')->name('companies.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'createCompany')->name('create');
                Route::post('/', 'addNewCompany')->name('store');
                Route::get('/{id}', 'companyDetails')->whereNumber('id')->name('details');
                Route::post('/{id}/login', 'loginAsCompany')->whereNumber('id')->name('login');
                Route::get('/{id}/edit', 'editCompany')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'updateCompany')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'deleteCompany')->whereNumber('id')->name('destroy');
            });

            // Customers
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::get('/', 'showCustomers')->name('index');
                Route::get('/create', 'createCustomer')->name('create');
                Route::post('/', 'storeCustomer')->name('store');
                Route::get('/{id}/edit', 'editCustomer')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'updateCustomer')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'destroyCustomer')->whereNumber('id')->name('destroy');
                Route::post('/{id}/sign-out', 'signOutCustomer')->whereNumber('id')->name('sign-out');
                Route::post('/{id}/app-status', 'updateCustomerAppStatus')->whereNumber('id')->name('app-status');
                Route::post('/force-logout', 'forceLogoutCustomers')->name('force-logout');
            });

            // Suppliers
            Route::prefix('suppliers')->name('suppliers.')->group(function () {
                Route::get('/', 'showSuppliers')->name('index');
                Route::get('/create', 'createSupplier')->name('create');
                Route::post('/', 'storeSupplier')->name('store');
                Route::get('/{id}/edit', 'editSupplier')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'updateSupplier')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'destroySupplier')->whereNumber('id')->name('destroy');
            });

            // Products catalog
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', 'showProducts')->name('index');
                Route::get('/create', 'createProduct')->name('create');
                Route::post('/', 'storeProduct')->name('store');
                Route::get('/{id}/edit', 'editProduct')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'updateProduct')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'destroyProduct')->whereNumber('id')->name('destroy');
            });

            // Calculator appliance groups
            Route::prefix('calculator-groups')->name('calculator-groups.')->group(function () {
                Route::get('/', 'showApplianceGroups')->name('index');
                Route::get('/create', 'createApplianceGroup')->name('create');
                Route::post('/', 'storeApplianceGroup')->name('store');
                Route::get('/{id}/edit', 'editApplianceGroup')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'updateApplianceGroup')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'destroyApplianceGroup')->whereNumber('id')->name('destroy');
            });

            // Company carousel
            Route::prefix('company-carousel')->name('company-carousel.')->group(function () {
                Route::get('/', 'companyCarouselIndex')->name('index');
                Route::get('/create', 'companyCarouselCreate')->name('create');
                Route::post('/', 'companyCarouselStore')->name('store');
                Route::get('/{id}/edit', 'companyCarouselEdit')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'companyCarouselUpdate')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'companyCarouselDestroy')->whereNumber('id')->name('destroy');
            });

            // Mobile app versions
            Route::prefix('app-versions')->name('app-versions.')->group(function () {
                Route::get('/', 'appVersions')->name('index');
                Route::get('/create', 'createAppVersion')->name('create');
                Route::post('/', 'storeAppVersion')->name('store');
                Route::get('/{id}/edit', 'editAppVersion')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'updateAppVersion')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'destroyAppVersion')->whereNumber('id')->name('destroy');

                // Android APK for the home page "Direct download" (stored in storage/app/private/apk)
                Route::post('/apk/chunk', 'uploadApkChunk')->name('apk.chunk');
                Route::post('/apk/finish', 'finishApkUpload')->name('apk.finish');
                Route::patch('/apk/visibility', 'toggleApkDownload')->name('apk.visibility');
                Route::delete('/apk', 'deleteApk')->name('apk.destroy');

                // Show/hide the home page Google Play button
                Route::patch('/play-store/visibility', 'togglePlayStoreButton')->name('play-store.visibility');
            });

            // Quote requests: view, plus cancel / reassign / nudge companies
            Route::prefix('quote-requests')->name('quote-requests.')->group(function () {
                Route::get('/', 'quoteRequests')->name('index');
                Route::get('/{id}', 'quoteRequestDetails')->whereNumber('id')->name('show');
                Route::post('/{id}/cancel', 'cancelQuoteRequest')->whereNumber('id')->name('cancel');
                Route::post('/{id}/reassign', 'reassignQuoteRequest')->whereNumber('id')->name('reassign');
                Route::post('/{id}/nudge', 'nudgeCompaniesForQuote')->whereNumber('id')->middleware('throttle:10,1')->name('nudge');
            });

            // Background jobs: queue health and failed emails/pushes with retry
            Route::prefix('background-jobs')->name('queue.')->group(function () {
                Route::get('/', 'backgroundJobs')->name('index');
                Route::post('/retry-all', 'retryAllFailedJobs')->name('retry-all');
                Route::delete('/', 'deleteAllFailedJobs')->name('destroy-all');
                Route::get('/{uuid}', 'showFailedJob')->whereUuid('uuid')->name('show');
                Route::post('/{uuid}/retry', 'retryFailedJob')->whereUuid('uuid')->name('retry');
                Route::delete('/{uuid}', 'deleteFailedJob')->whereUuid('uuid')->name('destroy');
            });

            // Push notifications
            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/', 'notifications')->name('index');
                Route::get('/create', 'createNotification')->name('create');
                Route::post('/', 'storeNotification')->name('store');
                Route::get('/{notification}', 'showNotification')->whereNumber('notification')->name('show');
                Route::delete('/{notification}', 'destroyNotification')->whereNumber('notification')->name('destroy');
            });

            // Customer ratings of companies
            Route::prefix('ratings')->name('ratings.')->group(function () {
                Route::get('/', 'ratings')->name('index');
                Route::get('/{id}', 'showRating')->whereNumber('id')->name('show');
                Route::post('/{id}/visibility', 'toggleRatingVisibility')->whereNumber('id')->name('visibility');
                Route::delete('/{id}', 'destroyRating')->whereNumber('id')->name('destroy');
            });

            // Rating questions (Price, Service, ...)
            Route::prefix('rating-questions')->name('rating-questions.')->group(function () {
                Route::get('/', 'ratingQuestions')->name('index');
                Route::get('/create', 'createRatingQuestion')->name('create');
                Route::post('/', 'storeRatingQuestion')->name('store');
                Route::get('/{id}/edit', 'editRatingQuestion')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'updateRatingQuestion')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'destroyRatingQuestion')->whereNumber('id')->name('destroy');
            });

            // Rating values (labels of the 1-4 answer scale)
            Route::get('/rating-values', 'ratingValues')->name('rating-values.index');
            Route::put('/rating-values', 'updateRatingValues')->name('rating-values.update');

            // Send Email: admin writes to a solar company (from info@, bcc the owner)
            Route::get('/send-email', 'sendEmail')->name('send-email.index');
            Route::post('/send-email', 'sendEmailSubmit')->middleware('throttle:20,1')->name('send-email.send');

            // App settings (open quote limit, ...)
            Route::get('/settings', 'settings')->name('settings.index');
            Route::put('/settings', 'updateSettings')->name('settings.update');
            Route::post('/settings/customer-quote-limit', 'updateCustomerQuoteLimit')->name('settings.customer-quote-limit');

            // API servers the app can use: one active, each online/offline (GET /api/v1/api-config)
            Route::prefix('settings/api-endpoints')->name('settings.api-endpoints.')->group(function () {
                Route::post('/', 'storeApiEndpoint')->name('store');
                Route::put('/{id}', 'updateApiEndpoint')->whereNumber('id')->name('update');
                Route::patch('/{id}/activate', 'activateApiEndpoint')->whereNumber('id')->name('activate');
                Route::delete('/{id}', 'destroyApiEndpoint')->whereNumber('id')->name('destroy');
            });

            // Database backups: create, download, delete (files in storage/app/private/db)
            Route::prefix('database-backups')->name('backups.')->group(function () {
                Route::get('/', 'databaseBackups')->name('index');
                Route::post('/', 'createDatabaseBackup')->middleware('throttle:5,1')->name('store');
                Route::get('/{file}/download', 'downloadDatabaseBackup')->where('file', '[A-Za-z0-9_\-]+\.sql')->name('download');
                Route::delete('/{file}', 'deleteDatabaseBackup')->where('file', '[A-Za-z0-9_\-]+\.sql')->name('destroy');
            });

            // Activity logs (admins, companies and customers). Admin only.
            Route::prefix('activity-logs')->name('logs.')->group(function () {
                Route::get('/', 'activityLogs')->name('index');
                Route::get('/export', 'exportActivityLogs')->name('export');
                Route::get('/{id}', 'showActivityLog')->whereNumber('id')->name('show');
            });
        });
    });
});

/*
|--------------------------------------------------------------------------
| Solar company panel
|--------------------------------------------------------------------------
*/

Route::prefix('company')->name('company.')->group(function () {

    // /company -> dashboard when logged in, otherwise the login page
    Route::get('/', function () {
        return auth('company')->check()
            ? redirect()->route('company.dashboard')
            : redirect()->route('company.login');
    })->name('index');

    // Authentication (same Blade as the admin login)
    Route::controller(CompanyAuthController::class)->group(function () {
        Route::get('/login', 'showLogin')->name('login');
        Route::post('/login', 'login')->middleware('throttle:10,1')->name('login.submit');

        // Forgot password: email check + OTP, then reset page
        Route::post('/forgot-password', 'checkResetEmail')->middleware('throttle:password-email')->name('password.email');
        Route::get('/reset-password', 'showResetPassword')->name('password.reset');
        Route::post('/reset-password', 'resetPassword')->middleware('throttle:password-update')->name('password.update');
        Route::post('/reset-password/resend', 'resendResetOtp')->middleware('throttle:password-resend')->name('password.resend');
    });

    Route::middleware('company.auth')->group(function () {

        Route::post('/logout', [CompanyAuthController::class, 'logout'])->name('logout');

        Route::controller(CompanyMainController::class)->group(function () {

            Route::get('/dashboard', 'dashboard')->name('dashboard');

            // Reports (date range via ?preset=30d or ?preset=custom&from=&to=)
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', 'reports')->name('index');
                Route::get('/export/{section}', 'exportReport')->whereIn('section', ['offers', 'projects', 'customers'])->name('export');
            });

            // Company profile
            Route::get('/profile', 'index')->name('profile');
            Route::put('/profile', 'updateProfile')->name('profile.update');

            // Products
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', 'showProducts')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{product}/edit', 'edit')->whereNumber('product')->name('edit');
                Route::put('/{product}', 'update')->whereNumber('product')->name('update');
                Route::delete('/{product}', 'destroy')->whereNumber('product')->name('destroy');
            });

            // Quote requests and offers
            Route::prefix('quote-requests')->name('quote-requests.')->group(function () {
                Route::get('/', 'quoteRequests')->name('index');
                Route::get('/{id}', 'quoteRequestDetails')->whereNumber('id')->name('show');
                Route::post('/{id}/offer', 'submitQuoteOffer')->whereNumber('id')->name('offer.store');
            });

            // Push notifications
            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/', 'notifications')->name('index');
                Route::get('/create', 'createNotification')->name('create');
                Route::post('/', 'storeNotification')->name('store');
                Route::get('/{notification}', 'showNotification')->whereNumber('notification')->name('show');
                Route::delete('/{notification}', 'destroyNotification')->whereNumber('notification')->name('destroy');
            });

            // Customers who accepted this company's offers
            Route::get('/customers', 'companyCustomers')->name('customers.index');

            // Ratings customers gave this company
            Route::get('/ratings', 'ratings')->name('ratings.index');
        });

        // Projects (offers accepted by customers)
        Route::controller(ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{id}', 'show')->whereNumber('id')->name('show');
            Route::post('/{id}/progress', 'updateProgress')->whereNumber('id')->name('progress.update');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Maintenance
|--------------------------------------------------------------------------
*/

// Clear cached config, routes, views and app cache. Admins only.
Route::get('/clear', function () {
    Artisan::call('optimize:clear');

    return 'Caching, routes, and configuration cleared successfully.';
})->middleware('admin.auth')->name('clear');

/*
|--------------------------------------------------------------------------
| Local development only (not registered on production)
|--------------------------------------------------------------------------
*/

if (app()->isLocal()) {
    // Test push notifications
    Route::get('/push', [WebsiteController::class, 'testPush'])->name('testPush');

    // Preview the customer welcome emails
    $welcomeEmailData = fn(string $customerName) => [
        'customerName' => $customerName,
        'homeUrl' => url('/'),
        'supportEmail' => config('mail.from.address'),
        'privacyUrl' => url('/privacy-policy'),
        'termsUrl' => url('/terms'),
        'logoUrl' => asset('visitors/images/sun.png'),
    ];

    Route::get('/email-en', fn() => view('emails.customer.welcome-en', $welcomeEmailData('Mohammed')));
    Route::get('/email-ar', fn() => view('emails.customer.welcome-ar', $welcomeEmailData('محمد')));
}
