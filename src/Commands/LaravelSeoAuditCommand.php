<?php

namespace AryaAzadeh\LaravelSeoAudit\Commands;

use AryaAzadeh\LaravelSeoAudit\AuditRunner;
use AryaAzadeh\LaravelSeoAudit\Data\SeoReport;
use AryaAzadeh\LaravelSeoAudit\Reporting\HtmlReporter;
use AryaAzadeh\LaravelSeoAudit\Reporting\JsonReporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LaravelSeoAuditCommand extends Command
{
    protected $signature = 'seo:audit
        {--format=table : Output format (table|json|html)}
        {--fail-on= : Fail when error or critical issues are found (error|critical)}
        {--output= : Optional output file path}
        {--max-pages=100 : Maximum pages to scan}';

    protected $aliases = ['laravel-seo-audit'];

    protected $description = 'Run a deterministic SEO audit for your Laravel application.';

    public function handle(): int
    {
        /** @var AuditRunner $runner */
        $runner = app(AuditRunner::class);
        $report = $runner->run((int) $this->option('max-pages'));

        $format = strtolower((string) $this->option('format'));
        $output = $this->option('output');

        if ($format === 'json') {
            $this->emitReport((new JsonReporter)->render($report), $output);
        } elseif ($format === 'html') {
            $this->emitReport((new HtmlReporter)->render($report), $output);
        } else {
            $this->renderTable($report);
        }

        return $this->exitCode($report);
    }

    private function renderTable(SeoReport $report): void
    {
        $summary = $report->summary;
        $this->info('SEO Audit Summary');
        $this->table(['Metric', 'Value'], [
            ['Pages', (string) $summary->pages],
            ['Issues', (string) $summary->issues],
            ['Score', (string) $summary->score],
            ['Info', (string) $summary->info],
            ['Warning', (string) $summary->warning],
            ['Error', (string) $summary->error],
            ['Critical', (string) $summary->critical],
        ]);

        $rows = [];
        foreach ($report->pages as $page) {
            foreach ($page->issues as $issue) {
                $rows[] = [$page->url, $issue->severity->value, $issue->rule, $issue->message];
            }
        }

        if ($rows !== []) {
            $this->table(['URL', 'Severity', 'Rule', 'Message'], $rows);
        }
    }

    private function emitReport(string $contents, ?string $output): void
    {
        if (is_string($output) && $output !== '') {
            File::put($output, $contents);
            $this->info("Report written to {$output}");

            return;
        }

        $this->line($contents);
    }

    private function exitCode(SeoReport $report): int
    {
        $failOn = strtolower((string) ($this->option('fail-on') ?: config('seo-audit.ci.fail_on', 'error')));
        $summary = $report->summary;

        if ($summary->critical > 0) {
            return 3;
        }

        if ($failOn === 'error' && $summary->error > 0) {
            return 2;
        }

        return self::SUCCESS;
    }
}
