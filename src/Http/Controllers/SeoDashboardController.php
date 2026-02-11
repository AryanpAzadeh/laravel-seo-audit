<?php

namespace AryaAzadeh\LaravelSeoAudit\Http\Controllers;

use AryaAzadeh\LaravelSeoAudit\Models\AuditRun;
use Illuminate\Http\Response;

class SeoDashboardController
{
    public function __invoke(): Response
    {
        $latestRun = AuditRun::query()->with(['pages.issues'])->latest('id')->first();

        return response()->view('laravel-seo-audit::dashboard', [
            'latestRun' => $latestRun,
        ]);
    }
}
