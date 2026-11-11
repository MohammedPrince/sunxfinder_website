<?php

use App\Http\Controllers\Api\v1\customers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// App start-up: which API server to call and whether it is online (Admin → Settings). No login.
Route::get('/v1/api-config', [MainController::class, 'apiConfig'])->middleware(['json.res', 'throttle:120,1']);
// Public company picker endpoint; the authenticated customer-scoped alias remains available below.
Route::get('/v1/companies', [MainController::class, 'getCompanies'])->middleware(['json.res', 'throttle:60,1']);

Route::prefix('v1/customer')->middleware('json.res')->group(function () {
    // Public routes
    Route::post('/login', [MainController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/register', [MainController::class, 'createAccount'])->middleware('throttle:5,1');

    // Forgot password (no token): send code -> verify code -> set new password
    Route::prefix('password')->group(function () {
        Route::post('/forgot', [MainController::class, 'forgotPassword'])->middleware('throttle:customer-password-forgot');
        Route::post('/verify-otp', [MainController::class, 'verifyPasswordResetOtp'])->middleware('throttle:customer-password-verify');
        Route::post('/reset', [MainController::class, 'resetPassword'])->middleware('throttle:customer-password-reset');
    });

    // Legal pages (Admin → System → Privacy & Terms). No token: the app shows the Terms
    // before sign-in. ?lang=en|ar, else the signed-in customer's language, else English.
    Route::get('/privacy-policy', [MainController::class, 'privacyPolicy'])->middleware('throttle:60,1');
    Route::get('/terms', [MainController::class, 'terms'])->middleware('throttle:60,1');

    // Protected routes: valid Sanctum token required
    Route::middleware('auth:sanctum')->group(function () {

        // Lookups and catalogue. The app reloads these on every pull to
        // refresh, so they need a higher limit than login.

        Route::post('/main-data', [MainController::class, 'mainData'])->middleware('throttle:60,1');

        Route::get('/cities', [MainController::class, 'getCities'])->middleware('throttle:60,1');
        Route::get('/companies', [MainController::class, 'getCompanies'])->middleware('throttle:60,1');
        Route::post('/complaints', [MainController::class, 'storeComplaint'])->middleware('throttle:20,1');
        Route::get('/complaints', [MainController::class, 'myComplaints'])->middleware('throttle:60,1');
        Route::get('/companies/{id}', [MainController::class, 'getCompany'])->whereNumber('id')->middleware('throttle:60,1');
        // For "Open in App" links from the website: sunxfinder://company/{slug}
        Route::get('/companies/slug/{slug}', [MainController::class, 'getCompanyBySlug'])->where('slug', '[A-Za-z0-9-]+')->middleware('throttle:60,1');
        Route::get('/services', [MainController::class, 'getServices'])->middleware('throttle:60,1');
        Route::get('/carousels', [MainController::class, 'getCarousels'])->middleware('throttle:60,1');
        Route::get('/sliders', [MainController::class, 'getAppSliders'])->middleware('throttle:60,1');
        Route::get('/home-sections', [MainController::class, 'getHomeSections'])->middleware('throttle:60,1');
        Route::get('/calculator-items', [MainController::class, 'appliancesCalculator'])->middleware('throttle:60,1');
        // A load calculated on the website before sign-up (docs: sunxfinder_app/docs/website-load-handoff.md).
        Route::get('/pending-load', [MainController::class, 'getPendingLoad'])->middleware('throttle:60,1');
        Route::delete('/pending-load', [MainController::class, 'clearPendingLoad'])->middleware('throttle:60,1');

        Route::post('/my-quotes', [MainController::class, 'getMyQuotes'])->middleware('throttle:60,1')->name('my-quotes');
        Route::post('/archive', [MainController::class, 'archiveQuote'])->middleware('throttle:60,1')->name('archive');
        Route::post('/quote', [MainController::class, 'storeQuote'])->middleware('throttle:60,1')->name('quote');
        // Customer confirms the handover once the company finished installation (completes the project).
        Route::post('/quote-complete', [MainController::class, 'completeQuote'])->middleware('throttle:20,1')->name('quote-complete');
        // Instead of confirming the handover: the project goes back to the company.
        Route::post('/handover-problem', [MainController::class, 'reportHandoverProblem'])->middleware('throttle:10,1')->name('handover-problem');

        Route::post('/offer-accept', [MainController::class, 'acceptOffer'])->middleware('throttle:60,1')->name('offer-accept');
        Route::post('/offer-reject', [MainController::class, 'rejectOffer'])->middleware('throttle:60,1')->name('offer-reject');

        // Company package offers pushed in notifications (docs/api/customer-package-offers.md)
        Route::get('/package-offers/{id}', [MainController::class, 'getPackageOffer'])->whereNumber('id')->middleware('throttle:60,1');
        Route::post('/package-offers/{id}/accept', [MainController::class, 'acceptPackageOffer'])->whereNumber('id')->middleware('throttle:20,1');
        Route::post('/package-offers/{id}/decline', [MainController::class, 'declinePackageOffer'])->whereNumber('id')->middleware('throttle:20,1');

        // Ratings: rate the company whose offer was accepted (one per quote)
        Route::get('/rating-form', [MainController::class, 'getRatingForm'])->middleware('throttle:60,1');
        Route::post('/rating', [MainController::class, 'submitRating'])->middleware('throttle:20,1')->name('rating');
        Route::patch('/rating/{id}', [MainController::class, 'updateRating'])->whereNumber('id')->middleware('throttle:20,1')->name('rating.update');
        Route::delete('/rating/{id}', [MainController::class, 'deleteRating'])->whereNumber('id')->middleware('throttle:20,1')->name('rating.delete');

        Route::get('/profile', [MainController::class, 'getProfile'])->middleware('throttle:60,1');
        Route::post('/profile', [MainController::class, 'updateProfile'])->middleware('throttle:60,1');

        Route::get('/project-progress/{projectId}', [MainController::class, 'getProjectProgress'])->whereNumber('projectId')->name('customer.project-progress');

        Route::post('/device-token', [MainController::class, 'saveCustomerDeviceToken'])->middleware('throttle:60,1');
        Route::post('/ad-click', [MainController::class, 'recordAdClick'])->middleware('throttle:120,1');
        Route::get('/notification-preferences', [MainController::class, 'getNotificationPreferences'])->middleware('throttle:60,1');
        Route::post('/notification-preferences', [MainController::class, 'updateNotificationPreferences'])->middleware('throttle:60,1');
        Route::get('/notifications', [MainController::class, 'getNotifications'])->middleware('throttle:60,1');
        Route::get('/notifications/{id}', [MainController::class, 'getNotification'])->whereNumber('id')->middleware('throttle:60,1');
        Route::post('/notifications/{id}/read', [MainController::class, 'markNotificationAsRead']);
        Route::post('/notifications/read-all', [MainController::class, 'markAllNotificationsAsRead']);

        Route::post('/delete-account', [MainController::class, 'deleteAccount']);
        Route::post('/logout', [MainController::class, 'logout']);

    });
});
