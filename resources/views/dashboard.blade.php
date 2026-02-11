<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SEO Audit Dashboard</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 2rem; color: #111827; }
        .summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1.5rem; }
        .card { border: 1px solid #e5e7eb; border-radius: .5rem; padding: .75rem; background: #f9fafb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: .5rem; font-size: .875rem; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>SEO Audit Dashboard</h1>

    @if (! $latestRun)
        <p>No audit data found yet.</p>
    @else
        <div class="summary">
            <div class="card"><strong>Score</strong><br>{{ $latestRun->score }}</div>
            <div class="card"><strong>Status</strong><br>{{ $latestRun->status }}</div>
            <div class="card"><strong>Pages</strong><br>{{ data_get($latestRun->totals, 'pages', 0) }}</div>
            <div class="card"><strong>Issues</strong><br>{{ data_get($latestRun->totals, 'issues', 0) }}</div>
            <div class="card"><strong>Finished</strong><br>{{ optional($latestRun->finished_at)->toDateTimeString() }}</div>
        </div>

        <h2>Pages</h2>
        <table>
            <thead>
            <tr>
                <th>URL</th>
                <th>Status</th>
                <th>Issues</th>
                <th>Title</th>
            </tr>
            </thead>
            <tbody>
            @foreach($latestRun->pages as $page)
                <tr>
                    <td>{{ $page->url }}</td>
                    <td>{{ $page->status_code }}</td>
                    <td>{{ $page->issues_count }}</td>
                    <td>{{ $page->title }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
