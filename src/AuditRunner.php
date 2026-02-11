<?php

namespace AryaAzadeh\LaravelSeoAudit;

use AryaAzadeh\LaravelSeoAudit\Contracts\AnalyzerInterface;
use AryaAzadeh\LaravelSeoAudit\Contracts\CrawlerInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Data\SeoReport;
use AryaAzadeh\LaravelSeoAudit\Data\SeoRunSummary;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;
use AryaAzadeh\LaravelSeoAudit\Models\AuditIssue;
use AryaAzadeh\LaravelSeoAudit\Models\AuditPage;
use AryaAzadeh\LaravelSeoAudit\Models\AuditRun;
use AryaAzadeh\LaravelSeoAudit\Support\SeoScoreCalculator;
use DateTimeImmutable;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuditRunner
{
    public function __construct(
        private CrawlerInterface $crawler,
        private AnalyzerInterface $analyzer,
        private RuleEngine $ruleEngine,
        private SeoScoreCalculator $scoreCalculator,
        private Kernel $kernel,
    ) {}

    public function run(int $maxPages = 100): SeoReport
    {
        $targets = $this->crawler->crawl($maxPages);
        $pages = [];

        foreach ($targets as $target) {
            [$html, $statusCode] = $this->fetchContent($target->path, $target->source, $target->url);
            $analysis = $this->analyzer->analyze($target, $html, $statusCode);
            $pages[] = $this->ruleEngine->evaluate($analysis);
        }

        $report = $this->buildReport($pages);
        $this->persistReport($report);

        return $report;
    }

    /** @return array{0: string|null, 1: int} */
    private function fetchContent(string $path, string $source, string $url): array
    {
        if ($source === 'route') {
            $response = $this->kernel->handle(Request::create($path, 'GET'));

            return [
                method_exists($response, 'getContent') ? $response->getContent() : null,
                method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200,
            ];
        }

        $contents = @file_get_contents($url);

        return [$contents !== false ? $contents : null, $contents !== false ? 200 : 500];
    }

    /** @param array<int, SeoPageResult> $pages */
    private function buildReport(array $pages): SeoReport
    {
        $severityCount = [
            Severity::Info->value => 0,
            Severity::Warning->value => 0,
            Severity::Error->value => 0,
            Severity::Critical->value => 0,
        ];

        $issueTotal = 0;

        foreach ($pages as $page) {
            foreach ($page->issues as $issue) {
                $severityCount[$issue->severity->value]++;
                $issueTotal++;
            }
        }

        $summary = new SeoRunSummary(
            pages: count($pages),
            issues: $issueTotal,
            score: $this->scoreCalculator->calculate($pages),
            info: $severityCount[Severity::Info->value],
            warning: $severityCount[Severity::Warning->value],
            error: $severityCount[Severity::Error->value],
            critical: $severityCount[Severity::Critical->value],
        );

        return new SeoReport(
            reportVersion: '1.0.0',
            generatedAt: new DateTimeImmutable(),
            summary: $summary,
            pages: $pages,
        );
    }

    private function persistReport(SeoReport $report): void
    {
        if (! Schema::hasTable('seo_audit_runs') || ! Schema::hasTable('seo_audit_pages') || ! Schema::hasTable('seo_audit_issues')) {
            return;
        }

        $summary = $report->summary;
        $run = AuditRun::query()->create([
            'status' => 'completed',
            'score' => $summary->score,
            'totals' => $summary->toArray(),
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        foreach ($report->pages as $pageResult) {
            $page = AuditPage::query()->create([
                'run_id' => $run->id,
                'url' => $pageResult->url,
                'source' => $pageResult->source,
                'status_code' => $pageResult->statusCode,
                'title' => $pageResult->title,
                'word_count' => $pageResult->wordCount,
                'issues_count' => count($pageResult->issues),
            ]);

            foreach ($pageResult->issues as $issue) {
                AuditIssue::query()->create([
                    'run_id' => $run->id,
                    'page_id' => $page->id,
                    'rule' => $issue->rule,
                    'severity' => $issue->severity->value,
                    'message' => $issue->message,
                    'context' => $issue->context,
                ]);
            }
        }
    }
}
