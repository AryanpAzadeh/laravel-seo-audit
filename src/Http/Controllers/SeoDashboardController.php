<?php

namespace AryaAzadeh\LaravelSeoAudit\Http\Controllers;

use AryaAzadeh\LaravelSeoAudit\Models\AuditIssue;
use AryaAzadeh\LaravelSeoAudit\Models\AuditRun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeoDashboardController
{
    public function __invoke(Request $request): Response
    {
        $latestRun = null;
        $recentRuns = collect();
        $trendRuns = collect();
        $ruleBreakdown = collect();
        $availableRules = collect();
        $recentIssues = $this->emptyPaginator($request);
        $totalIssuesForRun = 0;

        $hasRunsTable = Schema::hasTable('seo_audit_runs');
        $hasPagesTable = Schema::hasTable('seo_audit_pages');
        $hasIssuesTable = Schema::hasTable('seo_audit_issues');

        $selectedRunId = $request->integer('run');
        $selectedSeverity = strtolower(trim((string) $request->query('severity', '')));
        $selectedSeverity = in_array($selectedSeverity, ['info', 'warning', 'error', 'critical'], true)
            ? $selectedSeverity
            : '';
        $selectedRule = trim((string) $request->query('rule', ''));
        $searchTerm = trim((string) $request->query('q', ''));

        if ($hasRunsTable) {
            $recentRuns = AuditRun::query()->latest('id')->limit(20)->get();
            $trendRuns = $recentRuns->take(10)->reverse()->values();

            if ($recentRuns->isNotEmpty()) {
                $selectedRun = $selectedRunId > 0
                    ? $recentRuns->firstWhere('id', $selectedRunId)
                    : null;

                $selectedRun ??= $recentRuns->first();

                if ($selectedRun) {
                    $latestRunQuery = AuditRun::query()->whereKey($selectedRun->id);

                    if ($hasPagesTable) {
                        $latestRunQuery->with($hasIssuesTable ? ['pages.issues'] : ['pages']);
                    }

                    $latestRun = $latestRunQuery->first();
                }
            }

            if ($latestRun && $hasIssuesTable) {
                $totalIssuesForRun = (int) AuditIssue::query()
                    ->where('run_id', $latestRun->id)
                    ->count();

                $availableRules = AuditIssue::query()
                    ->where('run_id', $latestRun->id)
                    ->select('rule')
                    ->distinct()
                    ->orderBy('rule')
                    ->pluck('rule');

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
                    ->when($selectedSeverity !== '', static function (Builder $query) use ($selectedSeverity): void {
                        $query->where('severity', $selectedSeverity);
                    })
                    ->when($selectedRule !== '', static function (Builder $query) use ($selectedRule): void {
                        $query->where('rule', $selectedRule);
                    })
                    ->when($searchTerm !== '', static function (Builder $query) use ($searchTerm, $hasPagesTable): void {
                        $query->where(static function (Builder $innerQuery) use ($searchTerm, $hasPagesTable): void {
                            $innerQuery
                                ->where('rule', 'like', '%'.$searchTerm.'%')
                                ->orWhere('message', 'like', '%'.$searchTerm.'%');

                            if ($hasPagesTable) {
                                $innerQuery->orWhereHas('page', static function (Builder $pageQuery) use ($searchTerm): void {
                                    $pageQuery->where('url', 'like', '%'.$searchTerm.'%');
                                });
                            }
                        });
                    })
                    ->latest('id')
                    ->paginate(20)
                    ->withQueryString();
            }
        }

        [$summaryPages, $cleanPages, $non2xxPages, $averageWords] = $this->pageMetrics($latestRun, $hasPagesTable);
        $passRate = $summaryPages > 0 ? (int) round(($cleanPages / $summaryPages) * 100) : 100;

        $trendScoreMax = max(
            1,
            ...$trendRuns
                ->map(static fn (AuditRun $run): int => (int) $run->score)
                ->all(),
        );

        return response()->view('laravel-seo-audit::dashboard', [
            'latestRun' => $latestRun,
            'recentRuns' => $recentRuns,
            'trendRuns' => $trendRuns,
            'ruleBreakdown' => $ruleBreakdown,
            'recentIssues' => $recentIssues,
            'availableRules' => $availableRules,
            'selectedFilters' => [
                'run' => $latestRun?->id,
                'severity' => $selectedSeverity,
                'rule' => $selectedRule,
                'q' => $searchTerm,
            ],
            'totalIssuesForRun' => $totalIssuesForRun,
            'pageMetrics' => [
                'pages' => $summaryPages,
                'clean_pages' => $cleanPages,
                'non_2xx_pages' => $non2xxPages,
                'average_words' => $averageWords,
                'pass_rate' => $passRate,
            ],
            'trendMeta' => [
                'max_score' => $trendScoreMax,
            ],
        ]);
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} */
    private function pageMetrics(?AuditRun $run, bool $hasPagesTable): array
    {
        if (! $run || ! $hasPagesTable || ! $run->relationLoaded('pages')) {
            return [0, 0, 0, 0];
        }

        /** @var Collection<int, mixed> $pages */
        $pages = $run->pages;

        $totalPages = $pages->count();
        $cleanPages = $pages->filter(static fn ($page): bool => (int) $page->issues_count === 0)->count();
        $non2xxPages = $pages->filter(static fn ($page): bool => (int) $page->status_code < 200 || (int) $page->status_code >= 300)->count();
        $averageWords = $totalPages > 0 ? (int) round((float) $pages->avg('word_count')) : 0;

        return [$totalPages, $cleanPages, $non2xxPages, $averageWords];
    }

    private function emptyPaginator(Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: [],
            total: 0,
            perPage: 20,
            currentPage: 1,
            options: [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }
}
