<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SEO Audit Dashboard</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --good: #16a34a;
            --warn: #d97706;
            --bad: #dc2626;
            --critical: #7f1d1d;
            --accent: #1d4ed8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: radial-gradient(circle at top right, #e0e7ff 0%, var(--bg) 36%);
            color: var(--text);
            font-family: "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .container {
            width: min(1200px, 92vw);
            margin: 28px auto 48px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 18px;
            gap: 16px;
        }
        .title { margin: 0; font-size: clamp(1.4rem, 2.4vw, 2.1rem); letter-spacing: -0.02em; }
        .subtle { color: var(--muted); font-size: .92rem; margin-top: 6px; }
        .score-pill {
            min-width: 120px;
            text-align: center;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 9px 14px;
            background: var(--panel);
            font-weight: 700;
        }
        .grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            margin-bottom: 14px;
        }
        .card {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--panel);
            padding: 14px;
            min-height: 94px;
        }
        .label { color: var(--muted); font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; }
        .value { margin-top: 8px; font-weight: 700; font-size: 1.45rem; }
        .layout {
            display: grid;
            gap: 12px;
            grid-template-columns: 2fr 1fr;
        }
        .panel {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--panel);
            padding: 14px;
        }
        .panel h2 {
            margin: 0 0 10px;
            font-size: 1rem;
            letter-spacing: -.01em;
        }
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .9rem;
        }
        th, td {
            border-bottom: 1px solid var(--line);
            text-align: left;
            padding: 9px 8px;
            vertical-align: top;
        }
        th { color: var(--muted); font-weight: 600; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; }
        .sev {
            display: inline-flex;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid transparent;
        }
        .sev-info { color: #0369a1; background: #e0f2fe; border-color: #bae6fd; }
        .sev-warning { color: #92400e; background: #ffedd5; border-color: #fed7aa; }
        .sev-error { color: #991b1b; background: #fee2e2; border-color: #fecaca; }
        .sev-critical { color: #f8fafc; background: var(--critical); }
        .muted { color: var(--muted); }
        .bars { display: grid; gap: 8px; }
        .bar-row { display: grid; gap: 8px; grid-template-columns: 76px 1fr 34px; align-items: center; }
        .bar {
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }
        .bar > span { display: block; height: 100%; border-radius: 999px; }
        .bar-info { background: #0ea5e9; }
        .bar-warning { background: var(--warn); }
        .bar-error { background: var(--bad); }
        .bar-critical { background: var(--critical); }
        .hint {
            margin-top: 10px;
            font-size: .82rem;
            color: var(--muted);
        }
        @media (max-width: 980px) {
            .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 620px) {
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
@php
    $totals = is_array($latestRun?->totals) ? $latestRun->totals : [];
    $pagesCollection = ($latestRun && $latestRun->relationLoaded('pages')) ? $latestRun->pages : collect();
    $summaryPages = (int) data_get($totals, 'pages', $pagesCollection->count());
    $summaryIssues = (int) data_get($totals, 'issues', 0);
    $severityTotals = [
        'info' => (int) data_get($totals, 'info', 0),
        'warning' => (int) data_get($totals, 'warning', 0),
        'error' => (int) data_get($totals, 'error', 0),
        'critical' => (int) data_get($totals, 'critical', 0),
    ];
    $maxSeverity = max(1, ...array_values($severityTotals));
@endphp
<div class="container">
    <div class="header">
        <div>
            <h1 class="title">SEO Audit Dashboard</h1>
            <div class="subtle">Latest snapshot of scan quality, issue distribution, and crawl coverage.</div>
        </div>
        @if ($latestRun)
            <div class="score-pill">Score: {{ $latestRun->score }}</div>
        @endif
    </div>

    @if (! $latestRun)
        <div class="panel">
            <h2>No Audit Data</h2>
            <p class="muted">No runs are stored yet. Execute <code>php artisan seo:audit</code> once, then refresh this page.</p>
        </div>
    @else
        <div class="grid">
            <div class="card">
                <div class="label">Status</div>
                <div class="value">{{ strtoupper((string) $latestRun->status) }}</div>
            </div>
            <div class="card">
                <div class="label">Pages</div>
                <div class="value">{{ $summaryPages }}</div>
            </div>
            <div class="card">
                <div class="label">Total Issues</div>
                <div class="value">{{ $summaryIssues }}</div>
            </div>
            <div class="card">
                <div class="label">Warnings</div>
                <div class="value">{{ $severityTotals['warning'] }}</div>
            </div>
            <div class="card">
                <div class="label">Errors</div>
                <div class="value">{{ $severityTotals['error'] }}</div>
            </div>
            <div class="card">
                <div class="label">Finished At</div>
                <div class="value" style="font-size:1rem;">
                    {{ optional($latestRun->finished_at)->toDateTimeString() ?? '-' }}
                </div>
            </div>
        </div>

        <div class="layout">
            <section class="panel">
                <h2>Pages (Latest Run)</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>URL</th>
                            <th>Status</th>
                            <th>Issues</th>
                            <th>Words</th>
                            <th>Title</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($pagesCollection as $page)
                            <tr>
                                <td>{{ $page->url }}</td>
                                <td>{{ $page->status_code }}</td>
                                <td>{{ $page->issues_count }}</td>
                                <td>{{ $page->word_count }}</td>
                                <td>{{ $page->title ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted">No crawled pages persisted for this run.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="panel">
                <h2>Severity Distribution</h2>
                <div class="bars">
                    @foreach(['info', 'warning', 'error', 'critical'] as $sev)
                        <div class="bar-row">
                            <div class="muted">{{ strtoupper($sev) }}</div>
                            <div class="bar">
                                <span class="bar-{{ $sev }}" style="width: {{ ($severityTotals[$sev] / $maxSeverity) * 100 }}%"></span>
                            </div>
                            <div>{{ $severityTotals[$sev] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="hint">
                    Exit codes: <code>2</code> when errors exist, <code>3</code> when critical issues exist.
                </div>
            </aside>

            <section class="panel">
                <h2>Rule Breakdown</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Rule</th>
                            <th>Severity</th>
                            <th>Count</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($ruleBreakdown as $item)
                            <tr>
                                <td>{{ $item->rule }}</td>
                                <td>
                                    <span class="sev sev-{{ $item->severity }}">{{ $item->severity }}</span>
                                </td>
                                <td>{{ $item->total }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="muted">No issue rows found for the latest run.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <h2>Recent Runs</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Score</th>
                            <th>Issues</th>
                            <th>Finished</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($recentRuns as $run)
                            <tr>
                                <td>#{{ $run->id }}</td>
                                <td>{{ $run->score }}</td>
                                <td>{{ (int) data_get($run->totals, 'issues', 0) }}</td>
                                <td>{{ optional($run->finished_at)->toDateTimeString() ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="muted">No runs stored yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" style="grid-column: 1 / -1;">
                <h2>Latest Issues</h2>
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
                        @forelse($recentIssues as $issue)
                            <tr>
                                <td><span class="sev sev-{{ $issue->severity }}">{{ $issue->severity }}</span></td>
                                <td>{{ $issue->rule }}</td>
                                <td>{{ optional($issue->page)->url ?? '-' }}</td>
                                <td>{{ $issue->message }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="muted">No issue records available for this run.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif
</div>
</body>
</html>
