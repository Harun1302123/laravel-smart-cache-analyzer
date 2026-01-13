<?php

use Illuminate\Support\Facades\Route;
use SmartCache\Analyzer\Http\Controllers\DashboardController;

Route::middleware(config('smart-cache.middleware', ['web']))
    ->prefix(config('smart-cache.dashboard_path', 'smart-cache'))
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('smart-cache.dashboard');
        Route::get('/api/stats', [DashboardController::class, 'stats'])->name('smart-cache.stats');
        Route::get('/api/recommendations', [DashboardController::class, 'recommendations'])->name('smart-cache.recommendations');
        Route::get('/api/queries', [DashboardController::class, 'queries'])->name('smart-cache.queries');
    });
