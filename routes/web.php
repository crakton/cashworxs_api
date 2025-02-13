<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Single entry point for admin dashboard
Route::prefix('app')->group(function () {
    Route::get('{any?}', function () {
        return view('admin.app');
    })->where('any', '.*')->name('admin.dashboard');
});
