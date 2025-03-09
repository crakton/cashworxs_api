<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Entry point for admin dashboard
Route::prefix('app')->group(function () {
    Route::get('', function () {
        return view('admin.app');
    })->name('app');

    Route::get('payments', function () {
        return view('admin.pages.payments');
    })->name('payments');
    Route::get('login', function () {
        return view('admin.pages.auth.login');
    })->name('login');
    Route::get('dashboard', function () {
        return view('admin.pages.dashboard')->name('dashboard');
    });
});
