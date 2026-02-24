<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SEO Audit Dashboard</title>
    <style>
        :root {
            --bg: #f2f4fb;
            --bg-2: #e8edf9;
            --panel: rgba(255, 255, 255, 0.88);
            --line: #d9dfef;
            --text: #0b1220;
            --muted: #6a768f;
            --accent: #0b63f6;
            --accent-2: #2f7bff;
            --success: #0f766e;
            --warning: #b45309;
            --danger: #b91c1c;
            --critical: #581c87;
            --radius: 16px;
            --shadow: 0 12px 32px rgba(11, 18, 32, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--text);
            background:
                radial-gradient(1000px 420px at 95% -8%, #d2ddff 0%, transparent 65%),
                radial-gradient(750px 420px at -8% -8%, #d6f2ff 0%, transparent 63%),
                linear-gradient(180deg, var(--bg-2) 0%, var(--bg) 36%, #f6f8fd 100%);
            font-family: "Space Grotesk", "Vazirmatn", "IBM Plex Sans Arabic", "Trebuchet MS", sans-serif;
            min-height: 100vh;
        }

        .container {
            width: min(1320px, 94vw);
            margin: 28px auto 44px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .title {
            margin: 0;
            line-height: 1.05;
            font-size: clamp(1.6rem, 2.8vw, 2.6rem);
            letter-spacing: -0.03em;
            font-weight: 800;
        }

        .subtitle {
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.93rem;
        }

        .score {
            min-width: 170px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--panel);
            backdrop-filter: blur(6px);
            box-shadow: var(--shadow);
            text-align: center;
            padding: 12px 18px;
            font-weight: 800;
            font-size: 1.08rem;
        }

        .score .muted {
            display: block;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--panel);
            backdrop-filter: blur(8px);
            box-shadow: var(--shadow);
            padding: 14px;
        }

        .controls {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .controls .field {
            display: grid;
            gap: 6px;
        }

        .controls label {
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
        }

        select,
        input[type="text"] {
            width: 100%;
            border: 1px solid #c7d2ea;
            background: #fff;
            color: var(--text);
            border-radius: 10px;
            padding: 10px 11px;
            font: inherit;
            font-size: 0.92rem;
        }

        .control-actions {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .btn {
            border-radius: 10px;
            border: 1px solid transparent;
            cursor: pointer;
            padding: 10px 12px;
            font: inherit;
            font-weight: 700;
            font-size: 0.86rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
        }

        .btn-secondary {
            color: #334155;
            background: #eef2ff;
            border-color: #dbe5ff;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .kpi {
            border: 1px solid var(--line);
            border-radius: 13px;
            background: #fff;
            padding: 11px 12px;
            min-height: 92px;
        }

        .kpi-label {
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: 0.07em;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .kpi-value {
            margin-top: 8px;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .kpi-sub {
            margin-top: 6px;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .layout {
            display: grid;
            grid-template-columns: 1.7fr 1fr;
            gap: 12px;
        }

        .panel h2 {
            margin: 0;
            font-size: 1.05rem;
            letter-spacing: -0.01em;
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 10px;
        }

        .small-muted {
            font-size: 0.8rem;
            color: var(--muted);
        }

        .status-row {
            display: grid;
            grid-template-columns: 90px 1fr 45px;
            gap: 8px;
            align-items: center;
            margin-bottom: 7px;
        }

        .status-bar {
            height: 10px;
            border-radius: 999px;
            background: #e6ebf5;
            overflow: hidden;
        }

        .status-bar > span {
            display: block;
            height: 100%;
            border-radius: 999px;
        }

        .status-info { background: #0ea5e9; }
        .status-warning { background: #f59e0b; }
        .status-error { background: #ef4444; }
        .status-critical { background: var(--critical); }

        .spark {
            width: 100%;
            height: 94px;
            border-radius: 12px;
            background: linear-gradient(180deg, #eef4ff, #f8fbff);
            border: 1px solid #d7e3ff;
            padding: 8px;
        }

        .spark svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .spark-grid {
            stroke: #d8e3fb;
            stroke-width: 0.8;
        }

        .spark-line {
            fill: none;
            stroke: var(--accent);
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .spark-dot {
            fill: #fff;
            stroke: var(--accent);
            stroke-width: 2;
        }

        .mini-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 8px;
        }

        .mini {
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            padding: 8px;
            text-align: center;
        }

        .mini .n {
            font-weight: 800;
            font-size: 1.03rem;
        }

        .mini .l {
            color: var(--muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.89rem;
        }

        th,
        td {
            border-bottom: 1px solid #e2e8f4;
            text-align: left;
            padding: 8px 7px;
            vertical-align: top;
        }

        th {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .mono {
            font-family: "IBM Plex Mono", "SFMono-Regular", Consolas, monospace;
            font-size: 0.83rem;
            word-break: break-all;
        }

        .sev {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 0.73rem;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .sev-info { color: #075985; background: #e0f2fe; border-color: #bae6fd; }
        .sev-warning { color: #854d0e; background: #fef3c7; border-color: #fde68a; }
        .sev-error { color: #991b1b; background: #fee2e2; border-color: #fecaca; }
        .sev-critical { color: #f8fafc; background: #6d28d9; border-color: #5b21b6; }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid #dbe3f0;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .status-ok { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .status-redirect { color: #92400e; background: #ffedd5; border-color: #fed7aa; }
        .status-client { color: #9f1239; background: #ffe4e6; border-color: #fecdd3; }
        .status-server { color: #7f1d1d; background: #fee2e2; border-color: #fecaca; }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pagination .info {
            color: var(--muted);
            font-size: 0.83rem;
        }

        .pagination .nav {
            display: inline-flex;
            gap: 7px;
        }

        .empty {
            color: var(--muted);
            padding: 12px 0;
            font-size: 0.9rem;
        }

        .full {
            grid-column: 1 / -1;
        }

        @media (max-width: 1220px) {
            .controls { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 760px) {
            .header { flex-direction: column; }
            .controls { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .control-actions { grid-column: 1 / -1; }
            .status-row { grid-template-columns: 72px 1fr 36px; }
        }
    </style>
</head>
<body>
@php
    $totals = is_array($latestRun?->totals) ? $latestRun->totals : [];
    $pagesCollection = ($latestRun && $latestRun->relationLoaded('pages')) ? $latestRun->pages : collect();
    $summaryPages = (int) data_get($pageMetrics ?? [], 'pages', (int) data_get($totals, 'pages', $pagesCollection->count()));
    $summaryIssues = (int) data_get($totals, 'issues', 0);
    $severityTotals = [
        'info' => (int) data_get($totals, 'info', 0),
        'warning' => (int) data_get($totals, 'warning', 0),
        'error' => (int) data_get($totals, 'error', 0),
        'critical' => (int) data_get($totals, 'critical', 0),
    ];
    $maxSeverity = max(1, ...array_values($severityTotals));

    $severityWeight = ['info' => 1, 'warning' => 2, 'error' => 3, 'critical' => 4];

    $ruleSummary = $ruleBreakdown
        ->groupBy('rule')
        ->map(static function ($items) use ($severityWeight) {
            $worstSeverity = 'info';

            foreach ($items as $item) {
                $candidate = (string) $item->severity;
                if (($severityWeight[$candidate] ?? 0) > ($severityWeight[$worstSeverity] ?? 0)) {
                    $worstSeverity = $candidate;
                }
            }

            return [
                'count' => (int) $items->sum('total'),
                'severity' => $worstSeverity,
            ];
        })
        ->sortByDesc('count');

    $topRiskPages = $pagesCollection
        ->sortByDesc(static fn ($page) => ((int) $page->issues_count * 1000) + (int) $page->status_code)
        ->take(12)
        ->values();

    $trendItems = collect($trendRuns ?? [])->values();
    $trendMaxScore = (int) data_get($trendMeta ?? [], 'max_score', 100);
    $trendMaxScore = max(1, $trendMaxScore);
    $trendCount = $trendItems->count();
    $sparkPoints = $trendItems->map(static function ($run, $index) use ($trendCount, $trendMaxScore) {
        $x = $trendCount <= 1 ? 50 : ($index / ($trendCount - 1)) * 100;
        $score = (int) $run->score;
        $y = 100 - (($score / $trendMaxScore) * 100);

        return round($x, 2).','.round($y, 2);
    })->implode(' ');

    $selectedRunFilter = data_get($selectedFilters ?? [], 'run');
    $selectedSeverityFilter = (string) data_get($selectedFilters ?? [], 'severity', '');
    $selectedRuleFilter = (string) data_get($selectedFilters ?? [], 'rule', '');
    $selectedSearchFilter = (string) data_get($selectedFilters ?? [], 'q', '');

    $passRate = (int) data_get($pageMetrics ?? [], 'pass_rate', 100);
    $cleanPages = (int) data_get($pageMetrics ?? [], 'clean_pages', 0);
    $non2xxPages = (int) data_get($pageMetrics ?? [], 'non_2xx_pages', 0);
    $averageWords = (int) data_get($pageMetrics ?? [], 'average_words', 0);

    $issuesPaginator = $recentIssues;
@endphp
<div class="container">
    <div class="header">
        <div>
            <h1 class="title">SEO Audit Dashboard</h1>
            <div class="subtitle">Operational overview of crawl quality, issue patterns, and page-level SEO health.</div>
        </div>
        @if ($latestRun)
            <div class="score">
                <span class="muted">Selected Run</span>
                Score: {{ (int) $latestRun->score }}
            </div>
        @endif
    </div>

    @if (! $latestRun)
        <section class="panel">
            <h2 style="margin:0 0 8px;">No Audit Data</h2>
            <div class="empty">No runs are stored yet. Execute <code>php artisan seo:audit</code> once, then refresh this page.</div>
        </section>
    @else
        <section class="panel" style="margin-bottom:12px;">
            <form method="GET" action="{{ route('seo-audit.dashboard') }}" class="controls">
                <div class="field">
                    <label for="run">Run</label>
                    <select id="run" name="run">
                        @foreach($recentRuns as $run)
                            <option value="{{ $run->id }}" @selected((int) $selectedRunFilter === (int) $run->id)>
                                #{{ $run->id }} | Score {{ (int) $run->score }} | {{ optional($run->finished_at)->toDateTimeString() ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="severity">Severity</label>
                    <select id="severity" name="severity">
                        <option value="">All</option>
                        @foreach(['info', 'warning', 'error', 'critical'] as $sev)
                            <option value="{{ $sev }}" @selected($selectedSeverityFilter === $sev)>{{ strtoupper($sev) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="rule">Rule</label>
                    <select id="rule" name="rule">
                        <option value="">All</option>
                        @foreach($availableRules as $rule)
                            <option value="{{ $rule }}" @selected($selectedRuleFilter === $rule)>{{ $rule }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="q">Search</label>
                    <input id="q" name="q" value="{{ $selectedSearchFilter }}" type="text" placeholder="rule, message, URL...">
                </div>
                <div class="control-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a class="btn btn-secondary" href="{{ route('seo-audit.dashboard', ['run' => $latestRun->id]) }}">Reset</a>
                </div>
            </form>
        </section>

        <section class="kpi-grid">
            <div class="kpi">
                <div class="kpi-label">Status</div>
                <div class="kpi-value">{{ strtoupper((string) $latestRun->status) }}</div>
                <div class="kpi-sub">Run #{{ (int) $latestRun->id }}</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Pages</div>
                <div class="kpi-value">{{ $summaryPages }}</div>
                <div class="kpi-sub">Crawled URLs</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Total Issues</div>
                <div class="kpi-value">{{ $summaryIssues }}</div>
                <div class="kpi-sub">Detected in run</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Pass Rate</div>
                <div class="kpi-value">{{ $passRate }}%</div>
                <div class="kpi-sub">{{ $cleanPages }} pages with zero issues</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Non-2xx</div>
                <div class="kpi-value">{{ $non2xxPages }}</div>
                <div class="kpi-sub">Redirect/Error pages</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Avg Words</div>
                <div class="kpi-value">{{ $averageWords }}</div>
                <div class="kpi-sub">Content density</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Finished</div>
                <div class="kpi-value" style="font-size:1.0rem;">{{ optional($latestRun->finished_at)->toDateTimeString() ?? '-' }}</div>
                <div class="kpi-sub">Generated snapshot</div>
            </div>
        </section>

        <div class="layout">
            <section class="panel">
                <div class="panel-head">
                    <h2>Coverage & Trend</h2>
                    <span class="small-muted">Last {{ max(1, $trendItems->count()) }} runs</span>
                </div>

                <div class="spark" aria-hidden="true">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none">
                        <line class="spark-grid" x1="0" y1="25" x2="100" y2="25"></line>
                        <line class="spark-grid" x1="0" y1="50" x2="100" y2="50"></line>
                        <line class="spark-grid" x1="0" y1="75" x2="100" y2="75"></line>
                        @if ($sparkPoints !== '')
                            <polyline class="spark-line" points="{{ $sparkPoints }}"></polyline>
                            @foreach($trendItems as $index => $run)
                                @php
                                    $x = $trendItems->count() <= 1 ? 50 : ($index / ($trendItems->count() - 1)) * 100;
                                    $y = 100 - (((int) $run->score / $trendMaxScore) * 100);
                                @endphp
                                <circle class="spark-dot" cx="{{ $x }}" cy="{{ $y }}" r="1.8"></circle>
                            @endforeach
                        @endif
                    </svg>
                </div>

                <div class="mini-grid">
                    <div class="mini">
                        <div class="n">{{ (int) ($trendItems->last()->score ?? $latestRun->score) }}</div>
                        <div class="l">Current Score</div>
                    </div>
                    <div class="mini">
                        <div class="n">{{ (int) ($trendItems->max('score') ?? $latestRun->score) }}</div>
                        <div class="l">Peak Score</div>
                    </div>
                    <div class="mini">
                        <div class="n">{{ (int) ($trendItems->min('score') ?? $latestRun->score) }}</div>
                        <div class="l">Lowest Score</div>
                    </div>
                </div>
            </section>

            <aside class="panel">
                <div class="panel-head">
                    <h2>Severity Distribution</h2>
                    <span class="small-muted">Run #{{ (int) $latestRun->id }}</span>
                </div>
                @foreach(['info', 'warning', 'error', 'critical'] as $sev)
                    <div class="status-row">
                        <div class="small-muted">{{ strtoupper($sev) }}</div>
                        <div class="status-bar">
                            <span class="status-{{ $sev }}" style="width: {{ ($severityTotals[$sev] / $maxSeverity) * 100 }}%"></span>
                        </div>
                        <div>{{ $severityTotals[$sev] }}</div>
                    </div>
                @endforeach
                <div class="small-muted" style="margin-top:8px;">
                    Exit code rules: <code>2</code> if errors exist, <code>3</code> if critical issues exist.
                </div>
            </aside>

            <section class="panel">
                <div class="panel-head">
                    <h2>High-Risk Pages</h2>
                    <span class="small-muted">Top by issues/status</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>URL</th>
                            <th>Status</th>
                            <th>Issues</th>
                            <th>Words</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($topRiskPages as $page)
                            @php
                                $statusCode = (int) $page->status_code;
                                $statusClass = 'status-ok';
                                if ($statusCode >= 500) {
                                    $statusClass = 'status-server';
                                } elseif ($statusCode >= 400) {
                                    $statusClass = 'status-client';
                                } elseif ($statusCode >= 300 || $statusCode < 200) {
                                    $statusClass = 'status-redirect';
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div class="mono">{{ $page->url }}</div>
                                    <div class="small-muted">{{ $page->title ?: '-' }}</div>
                                </td>
                                <td><span class="status-pill {{ $statusClass }}">{{ $statusCode }}</span></td>
                                <td>{{ (int) $page->issues_count }}</td>
                                <td>{{ (int) $page->word_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty">No page rows found for this run.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <h2>Rule Breakdown</h2>
                    <span class="small-muted">Grouped by rule</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Rule</th>
                            <th>Severity</th>
                            <th>Count</th>
                            <th>Share</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($ruleSummary as $rule => $item)
                            @php
                                $ruleCount = (int) data_get($item, 'count', 0);
                                $share = $summaryIssues > 0 ? round(($ruleCount / $summaryIssues) * 100, 1) : 0;
                            @endphp
                            <tr>
                                <td>{{ $rule }}</td>
                                <td><span class="sev sev-{{ data_get($item, 'severity', 'info') }}">{{ strtoupper((string) data_get($item, 'severity', 'info')) }}</span></td>
                                <td>{{ $ruleCount }}</td>
                                <td>{{ $share }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty">No issue rows found for this run.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel full">
                <div class="panel-head">
                    <h2>Latest Issues</h2>
                    <span class="small-muted">{{ $issuesPaginator->total() }} matched issue(s) @if($totalIssuesForRun > 0) out of {{ $totalIssuesForRun }} in this run @endif</span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Severity</th>
                            <th>Rule</th>
                            <th>Page</th>
                            <th>Message</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($issuesPaginator as $issue)
                            <tr>
                                <td><span class="sev sev-{{ $issue->severity }}">{{ strtoupper((string) $issue->severity) }}</span></td>
                                <td>{{ $issue->rule }}</td>
                                <td class="mono">{{ optional($issue->page)->url ?? '-' }}</td>
                                <td>{{ $issue->message }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty">No issues matched the current filters.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($issuesPaginator->hasPages())
                    <div class="pagination">
                        <div class="info">
                            Showing {{ $issuesPaginator->firstItem() }}-{{ $issuesPaginator->lastItem() }} of {{ $issuesPaginator->total() }}
                        </div>
                        <div class="nav">
                            @if ($issuesPaginator->onFirstPage())
                                <span class="btn btn-secondary" aria-disabled="true">Previous</span>
                            @else
                                <a class="btn btn-secondary" href="{{ $issuesPaginator->previousPageUrl() }}">Previous</a>
                            @endif

                            @if ($issuesPaginator->hasMorePages())
                                <a class="btn btn-secondary" href="{{ $issuesPaginator->nextPageUrl() }}">Next</a>
                            @else
                                <span class="btn btn-secondary" aria-disabled="true">Next</span>
                            @endif
                        </div>
                    </div>
                @endif
            </section>
        </div>
    @endif
</div>
</body>
</html>
