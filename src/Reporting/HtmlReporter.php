<?php

namespace AryaAzadeh\LaravelSeoAudit\Reporting;

use AryaAzadeh\LaravelSeoAudit\Contracts\ReporterInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoReport;

class HtmlReporter implements ReporterInterface
{
    public function render(SeoReport $report): string
    {
        $summary = $report->summary->toArray();

        $rows = collect($report->pages)->flatMap(function ($page): array {
            return array_map(
                static fn (array $issue): string => sprintf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    e($page->url),
                    e($issue['severity']),
                    e($issue['rule']),
                    e($issue['message']),
                ),
                array_map(static fn ($issue): array => $issue->toArray(), $page->issues),
            );
        })->implode('');

        return '<!doctype html><html><head><meta charset="utf-8"><title>SEO Audit</title></head><body>'
            .'<h1>SEO Audit Report</h1>'
            .'<p>Score: '.e((string) $summary['score']).'</p>'
            .'<p>Pages: '.e((string) $summary['pages']).' | Issues: '.e((string) $summary['issues']).'</p>'
            .'<table border="1" cellpadding="6" cellspacing="0"><thead><tr><th>URL</th><th>Severity</th><th>Rule</th><th>Message</th></tr></thead><tbody>'
            .$rows
            .'</tbody></table></body></html>';
    }
}
