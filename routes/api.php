<?php

use App\Http\Controllers\Api\v1\customers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('v1/customer')->middleware('json.res')->group(function () {
    // Public routes
    Route::post('/login', [MainController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/register', [MainController::class, 'createAccount'])->middleware('throttle:5,1');

    // Protected routes: valid Sanctum token required
    Route::middleware('auth:sanctum')->group(function () {

        // Lookups and catalogue. The app reloads these on every pull to
        // refresh, so they need a higher limit than login.
        Route::get('/cities', [MainController::class, 'getCities'])->middleware('throttle:60,1');
        Route::get('/companies', [MainController::class, 'getCompanies'])->middleware('throttle:60,1');
        Route::get('/companies/{id}', [MainController::class, 'getCompany'])->whereNumber('id')->middleware('throttle:60,1');
        Route::get('/services', [MainController::class, 'getServices'])->middleware('throttle:60,1');
        Route::get('/carousels', [MainController::class, 'getCarousels'])->middleware('throttle:60,1');
        Route::get('/calculator-items', [MainController::class, 'appliancesCalculator'])->middleware('throttle:60,1');

        Route::post('/my-quotes', [MainController::class, 'getMyQuotes'])->middleware('throttle:60,1')->name('my-quotes');
        Route::post('/quote', [MainController::class, 'storeQuote'])->middleware('throttle:60,1')->name('quote');

        Route::post('/offer-accept', [MainController::class, 'acceptOffer'])->middleware('throttle:60,1')->name('offer-accept');
        Route::post('/offer-reject', [MainController::class, 'rejectOffer'])->middleware('throttle:60,1')->name('offer-reject');

        Route::get('/profile', [MainController::class, 'getProfile'])->middleware('throttle:60,1');
        Route::post('/profile', [MainController::class, 'updateProfile'])->middleware('throttle:60,1');


        Route::get('/project-progress/{projectId}',[MainController::class, 'getProjectProgress'])->whereNumber('projectId')->name('customer.project-progress');



        Route::post('/logout', [MainController::class, 'logout']);


        // Add private customer routes here:
        // Route::put('/profile', ...);
        // Route::get('/saved-companies', ...);
        // Route::get('/quotes', ...);
        // Route::get('/notifications', ...);
    });
});
