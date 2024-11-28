<?php

use App\Http\Controllers\Api\OnboardingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Platforms\PaymentController;
use App\Http\Controllers\User\FeesController;
use App\Http\Controllers\User\SettingsController;
use App\Http\Controllers\User\TaxesController;
use App\Http\Controllers\User\UserController;

// onboarding
Route::get('onboarding', [OnboardingController::class, 'index']);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('otp/send', [AuthController::class, 'sendOTP']);
    Route::post('otp/verify', [AuthController::class, 'verifyOTP']);
    // protected routes requires authentication
    Route::middleware('auth:api')->group(function () {
        //verification
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// user
Route::prefix('user')->group(function () {
    Route::get('activity', [UserController::class, 'getActivities']);

    // protected user routes
    Route::middleware('auth:api')->group(function () {
        Route::get('greetings', [UserController::class, 'greetings']);
        // activities (taxes and fees)
        Route::get('activity/taxes', [TaxesController::class, 'getUserTaxes']);
        Route::post('activity/tax', [TaxesController::class, 'checkoutTax']);
        Route::get('activity/tax/{id}', [TaxesController::class, 'getUserTax']);
        Route::patch('activity/tax/{id}', [TaxesController::class, 'updateUserTax']);
        Route::delete('activity/tax/{id}', [TaxesController::class, 'dropUserTax']);
        Route::get('activity/fees', [FeesController::class, 'getUserFees']);
        Route::post('activity/checkout-fee', [FeesController::class, 'checkoutFee']);
        Route::get('activity/fee/{id}', [FeesController::class, 'getUserFee']);
        Route::patch('activity/fee/{id}', [FeesController::class, 'updateUserFee']);
        Route::delete('activity/fee/{id}', [FeesController::class, 'dropUserFee']);
    });
});

// platforms
Route::prefix('platforms')->group(function () {
    Route::get('states', [SettingsController::class, 'getStates']);
    Route::get('payment-options', [PaymentController::class, 'paymentOptions']);
    Route::middleware('auth:api')->group(function () {
        Route::post('payment/tax', [PaymentController::class, 'processTaxPayment']);
        Route::post('payment/fee', [PaymentController::class, 'processFeePayment']);
    });
});

// settings
Route::prefix('settings')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::post('language', [SettingsController::class, 'updateLanguage']);
    });
});

//oauth
Route::prefix('auth/oauth')->group(function () {
    // Route::get('{provider}', [OAuthController::class, 'redirectToProvider']);
    Route::get('{provider}/callback', [OAuthController::class, 'handleProviderCallback']);
});
