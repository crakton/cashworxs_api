<?php

use Illuminate\Support\Facades\Route;

// Entry point for admin dashboard
Route::prefix('app')->group(function () {
    Route::get('', function () {
        return view('app');
    })->name('app');

    // Authentication routes
    Route::get('signin', function() {
        return view('pages.login');
    })->name('signin');

    Route::get('signup', function() {
        return view('pages.register');
    })->name('signup');

    // Dashboard routes
    Route::get('dashboard', function () {
        return view('pages.dashboard');
    })->name('dashboard');

    Route::get('analytics', function () {
        return view('pages.analytics');
    })->name('analytics');

    // User management routes
    Route::get('users', function () {
        return view('pages.users.list');
    })->name('users.list');

    Route::get('users/activity', function () {
        return view('pages.users.activity');
    })->name('users.activity');

    // Payment routes
    Route::get('payments', function () {
        return view('pages.payments.list');
    })->name('payments');

    Route::get('invoices', function () {
        return view('pages.payments.invoices');
    })->name('invoices');

    // Fees & Taxes routes
    Route::get('fees', function () {
        return view('pages.services.fees');
    })->name('fees');

    Route::get('taxes', function () {
        return view('pages.services.taxes');
    })->name('taxes');

    // Onboarding routes
    Route::get('onboarding/checklist', function () {
        return view('pages.onboarding.checklist');
    })->name('onboarding.checklist');

    // Settings routes
    Route::get('settings', function () {
        return view('pages.settings.general');
    })->name('settings');

    Route::get('settings/language', function () {
        return view('pages.settings.language');
    })->name('settings.language');
});
