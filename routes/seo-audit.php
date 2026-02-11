<?php

use AryaAzadeh\LaravelSeoAudit\Http\Controllers\SeoDashboardController;
use Illuminate\Support\Facades\Route;

if (! config('seo-audit.dashboard.enabled', true)) {
    return;
}

Route::middleware(config('seo-audit.dashboard.middleware', ['web', 'auth']))
    ->prefix('seo-audit')
    ->name('seo-audit.')
    ->group(function (): void {
        Route::get('/dashboard', SeoDashboardController::class)
            ->name('dashboard')
            ->can((string) config('seo-audit.dashboard.ability', 'viewSeoAudit'));
    });
