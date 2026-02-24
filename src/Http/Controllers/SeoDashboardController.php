<?php

namespace AryaAzadeh\LaravelSeoAudit\Http\Controllers;

use AryaAzadeh\LaravelSeoAudit\Models\AuditIssue;
use AryaAzadeh\LaravelSeoAudit\Models\AuditRun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeoDashboardController
{
    public function __invoke(): Response
    {
        $latestRun = null;
        $recentRuns = collect();
        $ruleBreakdown = collect();
        $recentIssues = collect();

        $hasRunsTable = Schema::hasTable('seo_audit_runs');
        $hasPagesTable = Schema::hasTable('seo_audit_pages');
        $hasIssuesTable = Schema::hasTable('seo_audit_issues');

        if ($hasRunsTable) {
            $latestRunQuery = AuditRun::query()->latest('id');

            if ($hasPagesTable) {
                $latestRunQuery->with($hasIssuesTable ? ['pages.issues'] : ['pages']);
            }

            $latestRun = $latestRunQuery->first();
            $recentRuns = AuditRun::query()->latest('id')->limit(10)->get();

            if ($latestRun && $hasIssuesTable) {
                $ruleBreakdown = AuditIssue::query()
                    ->select(['rule', 'severity', DB::raw('COUNT(*) as total')])
                    ->where('run_id', $latestRun->id)
                    ->groupBy('rule', 'severity')
                    ->orderByDesc('total')
                    ->get();

                $recentIssues = AuditIssue::query()
                    ->where('run_id', $latestRun->id)
                    ->when($hasPagesTable, static function (Builder $query): void {
                        $query->with('page:id,url');
                    })
                    ->latest('id')
                    ->limit(50)
                    ->get();
            }
        }

        return response()->view('laravel-seo-audit::dashboard', [
            'latestRun' => $latestRun,
            'recentRuns' => $recentRuns,
            'ruleBreakdown' => $ruleBreakdown,
            'recentIssues' => $recentIssues,
        ]);
    }
}
