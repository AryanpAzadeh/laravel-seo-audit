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
            [$content, $statusCode] = $this->fetchRouteContentFollowingRedirects($path);
            if (
                (bool) config('seo-audit.crawl.route_http_fallback_on_error', true)
                && $statusCode >= 400
                && $statusCode < 600
            ) {
                [$httpContent, $httpStatusCode] = $this->fetchHttpContent($url);
                if ($httpContent !== null && $httpStatusCode >= 200 && $httpStatusCode < 400) {
                    return [$httpContent, $httpStatusCode];
                }
            }

            return [$content, $statusCode];
        }

        return $this->fetchHttpContent($url);
    }

    /** @return array{0: string|null, 1: int} */
    private function fetchRouteContentFollowingRedirects(string $path, bool $allowLocalizedRetry = true): array
    {
        $currentPath = $path;
        $visitedPaths = [];
        $maxRedirects = 3;

        for ($i = 0; $i <= $maxRedirects; $i++) {
            $response = $this->kernel->handle($this->makeInternalRequest($currentPath));
            $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
            $content = method_exists($response, 'getContent') ? $response->getContent() : null;

            if ($statusCode < 300 || $statusCode >= 400) {
                if ($allowLocalizedRetry && $statusCode === 404) {
                    $localizedResult = $this->tryLocalizedPathFallback($path);
                    if ($localizedResult !== null) {
                        return $localizedResult;
                    }
                }

                return [$content, $statusCode];
            }

            $location = '';
            if (property_exists($response, 'headers') && $response->headers !== null && method_exists($response->headers, 'get')) {
                $location = (string) $response->headers->get('Location', '');
            }
            $nextPath = $this->normalizeRedirectLocation($location);

            if ($nextPath === null || in_array($nextPath, $visitedPaths, true)) {
                return [$content, $statusCode];
            }

            $visitedPaths[] = $nextPath;
            $currentPath = $nextPath;
        }

        $response = $this->kernel->handle($this->makeInternalRequest($currentPath));

        return [
            method_exists($response, 'getContent') ? $response->getContent() : null,
            method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 500,
        ];
    }

    /** @return array{0: string|null, 1: int}|null */
    private function tryLocalizedPathFallback(string $path): ?array
    {
        $supportedLocales = array_keys((array) config('laravellocalization.supportedLocales', []));
        if ($supportedLocales === []) {
            return null;
        }

        $preferredLocale = (string) (config('laravellocalization.defaultLocale') ?: config('app.locale', ''));
        $orderedLocales = $preferredLocale !== ''
            ? array_values(array_unique(array_merge([$preferredLocale], $supportedLocales)))
            : $supportedLocales;

        if ($this->pathStartsWithAnyLocale($path, $supportedLocales)) {
            return null;
        }

        foreach ($orderedLocales as $locale) {
            $localizedPath = $path === '/'
                ? '/'.$locale
                : '/'.$locale.$path;

            [$content, $statusCode] = $this->fetchRouteContentFollowingRedirects($localizedPath, false);
            if ($statusCode >= 200 && $statusCode < 400) {
                return [$content, $statusCode];
            }
        }

        return null;
    }

    private function normalizeRedirectLocation(string $location): ?string
    {
        if ($location === '') {
            return null;
        }

        if (str_starts_with($location, '/')) {
            return $location;
        }

        $parts = parse_url($location);
        if ($parts === false || ! isset($parts['path'])) {
            return null;
        }

        $path = $parts['path'];
        if ($path === '') {
            $path = '/';
        } elseif (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?'.$parts['query'];
        }

        return $path;
    }

    private function pathStartsWithAnyLocale(string $path, array $locales): bool
    {
        $firstSegment = strtok(trim($path, '/'), '/');

        return is_string($firstSegment) && $firstSegment !== '' && in_array($firstSegment, $locales, true);
    }

    private function makeInternalRequest(string $path): Request
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $parts = parse_url($appUrl);

        $scheme = isset($parts['scheme']) && in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)
            ? strtolower((string) $parts['scheme'])
            : 'http';
        $host = isset($parts['host']) && $parts['host'] !== '' ? $parts['host'] : 'localhost';
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return Request::create(
            $path,
            'GET',
            [],
            [],
            [],
            [
                'HTTP_HOST' => $host,
                'SERVER_NAME' => $host,
                'SERVER_PORT' => (string) $port,
                'REQUEST_SCHEME' => $scheme,
                'HTTPS' => $scheme === 'https' ? 'on' : 'off',
            ],
        );
    }

    /** @return array{0: string|null, 1: int} */
    protected function fetchHttpContent(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'ignore_errors' => true,
                'follow_location' => 1,
                'max_redirects' => 3,
                'timeout' => 10,
                'header' => "User-Agent: LaravelSeoAudit/1.0\r\n",
            ],
        ]);

        $contents = @file_get_contents($url, false, $context);
        $statusCode = $this->extractStatusCodeFromHeaders($http_response_header ?? []);

        if ($contents === false) {
            return [null, $statusCode ?? 500];
        }

        return [$contents, $statusCode ?? 200];
    }

    /** @param array<int, mixed> $headers */
    private function extractStatusCodeFromHeaders(array $headers): ?int
    {
        foreach (array_reverse($headers) as $header) {
            if (! is_string($header)) {
                continue;
            }

            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/i', $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
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
            generatedAt: new DateTimeImmutable,
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
