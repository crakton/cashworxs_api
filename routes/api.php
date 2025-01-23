<?php

use App\Http\Controllers\Admin\ServiceFeesController;
use App\Http\Controllers\Admin\ServiceTaxesController;
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
Route::prefix('onboarding')->group(function () {
	Route::get('', [OnboardingController::class, 'index']);
	Route::middleware(['api', 'auth:admin'])->group(function () {
		Route::post('/add', [OnboardingController::class, 'addChecklist']);
		Route::put('/update/{id}', [OnboardingController::class, 'updateChecklist']);
	});
	// Only admin onboarding privilge
	Route::middleware(['auth:admin'])->group(function () {});
});

Route::prefix('auth')->group(function () {
	Route::post('register', [AuthController::class, 'register']);
	Route::post('login', [AuthController::class, 'login']);
	Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
	Route::post('reset-password', [AuthController::class, 'resetPassword']);
	Route::post('otp/send', [AuthController::class, 'sendOTP']);
	Route::post('otp/verify', [AuthController::class, 'verifyOTP']);

	// protected routes requires authentication
	Route::middleware(['api', 'auth:admin'])->group(function () {
		//verification
		Route::post('logout', [AuthController::class, 'logout']);
	});
});

Route::middleware(['api', 'auth:admin'])->group(function () {
	Route::prefix('transactions')->group(function () {

		Route::get('user', [UserController::class, 'getAllTransactions']);
	});
});

// user
Route::prefix('users')->group(function () {
	Route::get('activity', [UserController::class, 'getActivities']);
	Route::get('{id}', [UserController::class, 'getUser']);
	Route::get('', [UserController::class, 'getAllUsers']);

	// protected user routes
	Route::middleware(['api', 'auth:admin'])->group(function () {
		Route::get('greeting', [UserController::class, 'greetUser']);
		Route::delete('{id}', [UserController::class, 'dropUser']);
		Route::put('{id}', [UserController::class, 'updateUser']);
		// user activities (taxes and fees)
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

	Route::middleware(['api', 'auth:admin'])->group(function () {
		Route::post('payment/tax', [PaymentController::class, 'processTaxPayment']);
		Route::post('payment/fee', [PaymentController::class, 'processFeePayment']);
	});
});

// settings
Route::prefix('settings')->group(function () {
	Route::get('languages', [SettingsController::class, 'getLanguages']);

	Route::middleware(['api', 'auth:admin'])->group(function () {
		Route::post('language', [SettingsController::class, 'updateLanguage']);
		Route::get('language', [SettingsController::class, 'getLanguage']);
	});
});

//oauth
Route::prefix('auth/oauth')->group(function () {
	// Route::get('{provider}', [OAuthController::class, 'redirectToProvider']);
	Route::get('{provider}/callback', [OAuthController::class, 'handleProviderCallback']);
});

Route::prefix('services')->group(function () {
	Route::get('fees', [ServiceFeesController::class, 'getServiceFees']);
	Route::get('taxes', [ServiceTaxesController::class, 'getServiceTaxes']);
	// Fees & Taxes services
	Route::middleware(['api', 'auth:admin'])->group(function () {});
	// Only admin services privilge
	Route::middleware('auth:admin')->group(function () {
		// fees
		Route::post('fees', [ServiceFeesController::class, 'createServiceFee']);
		Route::put('fees/{id}', [ServiceFeesController::class, 'updateServiceFee']);
		Route::delete('fees/{id}', [ServiceFeesController::class, 'deleteServiceFee']);
		//taxes
		Route::post('taxes', [ServiceFeesController::class, 'createServiceTax']);
		Route::put('taxes/{id}', [ServiceFeesController::class, 'updateServiceTax']);
		Route::delete('taxes/{id}', [ServiceFeesController::class, 'deleteServiceTax']);
	});
});

//only meant for testing
Route::delete('users-smackdown', [UserController::class, 'smackUserDB']);
Route::delete('user-smackdown', [UserController::class, 'smackUser']);

Route::fallback(function (Request $request) {
	return response()->json([
		'error' => 'Route not found',
		'path' => $request->path(),
	], 404);
});
