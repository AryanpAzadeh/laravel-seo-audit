<?php

namespace AryaAzadeh\LaravelSeoAudit\Crawling;

use AryaAzadeh\LaravelSeoAudit\Contracts\CrawlerInterface;
use AryaAzadeh\LaravelSeoAudit\Data\CrawlTarget;
use Illuminate\Routing\Router;

class RouteCrawler implements CrawlerInterface
{
    /** @var array<int, string> */
    private const DEFAULT_SKIPPED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp',
        'pdf', 'zip', 'rar', '7z', 'tar', 'gz',
        'mp3', 'wav', 'ogg', 'mp4', 'webm', 'avi', 'mov',
        'css', 'js', 'json', 'xml', 'txt',
    ];

    public function __construct(private Router $router) {}

    public function crawl(int $maxPages = 100): array
    {
        $targetsByKey = [];
        $baseUrl = rtrim((string) config('app.url', 'http://localhost'), '/');
        $requiredMiddleware = (array) config('seo-audit.crawl.route_filters.middleware', ['web']);
        $excludeMiddleware = (array) config('seo-audit.crawl.route_filters.exclude_middleware', ['auth', 'verified', 'password.confirm', 'signed']);
        $excludeParameterized = (bool) config('seo-audit.crawl.exclude_parameterized_routes', true);
        $deduplicateLocalized = (bool) config('seo-audit.crawl.deduplicate_localized_routes', true);
        $supportedLocales = array_keys((array) config('laravellocalization.supportedLocales', []));
        $appLocale = (string) config('app.locale', '');

        foreach ($this->router->getRoutes() as $route) {
            $methods = $route->methods();

            if (! in_array('GET', $methods, true)) {
                continue;
            }

            $path = '/'.ltrim($route->uri(), '/');

            if ($excludeParameterized && str_contains($path, '{')) {
                continue;
            }

            $middlewares = $route->gatherMiddleware();
            $isBlocked = collect($middlewares)->contains(static function (string $middleware) use ($excludeMiddleware): bool {
                if (str_starts_with($middleware, 'can:')) {
                    return true;
                }

                return in_array($middleware, $excludeMiddleware, true) || str_starts_with($middleware, 'auth');
            });

            $hasRequiredMiddleware = collect($requiredMiddleware)->every(
                static fn (string $middleware): bool => in_array($middleware, $middlewares, true)
            );

            if ($isBlocked || ! $hasRequiredMiddleware) {
                continue;
            }

            $path = $this->applyActiveLocalePrefixIfNeeded(
                $path,
                $middlewares,
                $supportedLocales,
                $appLocale,
                $deduplicateLocalized,
            );

            $candidate = new CrawlTarget(
                url: $baseUrl.$path,
                path: $path,
                source: 'route',
                routeName: $route->getName(),
            );

            $key = $this->deduplicationKey($candidate->path, $candidate->url, $supportedLocales, $deduplicateLocalized);

            if (! isset($targetsByKey[$key])) {
                $targetsByKey[$key] = $candidate;

                continue;
            }

            $existing = $targetsByKey[$key];
            $existingLocalePrefix = $this->localePrefix($existing->path, $supportedLocales);
            $candidateLocalePrefix = $this->localePrefix($candidate->path, $supportedLocales);

            // Prefer locale-prefixed path when both localized and non-localized variants exist.
            if ($existingLocalePrefix === null && $candidateLocalePrefix !== null) {
                $targetsByKey[$key] = $candidate;

                continue;
            }

            // If both localized variants exist, prefer the current app locale.
            if ($existingLocalePrefix !== null && $candidateLocalePrefix !== null && $appLocale !== '') {
                if ($existingLocalePrefix !== $appLocale && $candidateLocalePrefix === $appLocale) {
                    $targetsByKey[$key] = $candidate;
                }
            }
        }

        $targets = array_values($targetsByKey);
        if ($targets === [] && (bool) config('seo-audit.crawl.http_fallback', true)) {
            $fallbackTarget = new CrawlTarget(
                url: $baseUrl,
                path: '/',
                source: 'http-fallback',
            );
            $fallbackKey = $this->deduplicationKey($fallbackTarget->path, $fallbackTarget->url, $supportedLocales, $deduplicateLocalized);
            $targetsByKey[$fallbackKey] = $fallbackTarget;
        }

        if ((bool) config('seo-audit.crawl.sitemap_discovery.enabled', false)) {
            $targetsByKey = $this->discoverTargetsFromSitemaps(
                $targetsByKey,
                $baseUrl,
                $supportedLocales,
                $deduplicateLocalized,
                $maxPages,
            );
        }

        if ((bool) config('seo-audit.crawl.link_discovery.enabled', false)) {
            $targetsByKey = $this->discoverAdditionalTargets(
                $targetsByKey,
                $baseUrl,
                $supportedLocales,
                $deduplicateLocalized,
                $maxPages,
            );
        }

        $targets = array_values($targetsByKey);
        if (count($targets) > $maxPages) {
            $targets = array_slice($targets, 0, $maxPages);
        }

        return $targets;
    }

    private function deduplicationKey(string $path, string $url, array $supportedLocales, bool $deduplicateLocalized): string
    {
        if (! $deduplicateLocalized || $supportedLocales === []) {
            return $url;
        }

        $segments = explode('/', trim($path, '/'));
        if ($segments !== [] && $segments[0] !== '' && in_array($segments[0], $supportedLocales, true)) {
            array_shift($segments);
        }

        $normalizedPath = '/'.implode('/', $segments);
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        return $normalizedPath;
    }

    private function localePrefix(string $path, array $supportedLocales): ?string
    {
        $firstSegment = strtok(trim($path, '/'), '/');

        if (is_string($firstSegment) && $firstSegment !== '' && in_array($firstSegment, $supportedLocales, true)) {
            return $firstSegment;
        }

        return null;
    }

    private function applyActiveLocalePrefixIfNeeded(
        string $path,
        array $middlewares,
        array $supportedLocales,
        string $appLocale,
        bool $deduplicateLocalized,
    ): string {
        if (! $deduplicateLocalized || $appLocale === '' || ! in_array($appLocale, $supportedLocales, true)) {
            return $path;
        }

        if ($this->localePrefix($path, $supportedLocales) !== null) {
            return $path;
        }

        $hasLocalizationMiddleware = collect($middlewares)->contains(static function (string $middleware): bool {
            return in_array($middleware, ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'], true);
        });

        if (! $hasLocalizationMiddleware) {
            return $path;
        }

        return $path === '/'
            ? '/'.$appLocale
            : '/'.$appLocale.$path;
    }

    /**
     * @param  array<string, CrawlTarget>  $targetsByKey
     * @param  array<int, string>  $supportedLocales
     * @return array<string, CrawlTarget>
     */
    private function discoverAdditionalTargets(
        array $targetsByKey,
        string $baseUrl,
        array $supportedLocales,
        bool $deduplicateLocalized,
        int $maxPages,
    ): array {
        $discoveryMaxPages = max(0, (int) config('seo-audit.crawl.link_discovery.max_pages', 120));
        if ($discoveryMaxPages === 0 || count($targetsByKey) >= $maxPages) {
            return $targetsByKey;
        }

        $seedFromRouteTargets = (bool) config('seo-audit.crawl.link_discovery.seed_from_route_targets', true);
        $seedPaths = (array) config('seo-audit.crawl.link_discovery.seed_paths', ['/']);

        $queue = [];
        $queued = [];

        if ($seedFromRouteTargets) {
            foreach ($targetsByKey as $target) {
                $queue[] = $target;
                $queued[$target->url] = true;
            }
        }

        foreach ($seedPaths as $seedPath) {
            if (! is_string($seedPath) || $seedPath === '') {
                continue;
            }

            $normalizedSeedPath = $this->normalizePath($seedPath);
            $seedTarget = new CrawlTarget(
                url: $baseUrl.$normalizedSeedPath,
                path: $normalizedSeedPath,
                source: 'discovery-seed',
            );

            $seedKey = $this->deduplicationKey($seedTarget->path, $seedTarget->url, $supportedLocales, $deduplicateLocalized);
            if (! isset($targetsByKey[$seedKey])) {
                $targetsByKey[$seedKey] = $seedTarget;
            }

            if (! isset($queued[$seedTarget->url])) {
                $queue[] = $seedTarget;
                $queued[$seedTarget->url] = true;
            }
        }

        $visited = [];
        $fetchedPages = 0;

        while ($queue !== [] && $fetchedPages < $discoveryMaxPages && count($targetsByKey) < $maxPages) {
            /** @var CrawlTarget $current */
            $current = array_shift($queue);

            if (isset($visited[$current->url])) {
                continue;
            }
            $visited[$current->url] = true;

            $html = $this->fetchDiscoveryHtml($current);
            if (! is_string($html) || trim($html) === '') {
                continue;
            }

            $fetchedPages++;

            foreach ($this->extractInternalPathsFromHtml($html, $current->url, $baseUrl) as $path) {
                $candidate = new CrawlTarget(
                    url: $baseUrl.$path,
                    path: $path,
                    source: 'discovered-link',
                );

                $key = $this->deduplicationKey($candidate->path, $candidate->url, $supportedLocales, $deduplicateLocalized);
                if (! isset($targetsByKey[$key])) {
                    $targetsByKey[$key] = $candidate;
                }

                if (! isset($queued[$candidate->url])) {
                    $queue[] = $candidate;
                    $queued[$candidate->url] = true;
                }

                if (count($targetsByKey) >= $maxPages) {
                    break;
                }
            }
        }

        return $targetsByKey;
    }

    /**
     * @param  array<string, CrawlTarget>  $targetsByKey
     * @param  array<int, string>  $supportedLocales
     * @return array<string, CrawlTarget>
     */
    private function discoverTargetsFromSitemaps(
        array $targetsByKey,
        string $baseUrl,
        array $supportedLocales,
        bool $deduplicateLocalized,
        int $maxPages,
    ): array {
        if (count($targetsByKey) >= $maxPages) {
            return $targetsByKey;
        }

        $seedPaths = (array) config('seo-audit.crawl.sitemap_discovery.seed_paths', ['/sitemap.xml', '/sitemap_index.xml']);
        $maxSitemaps = max(1, (int) config('seo-audit.crawl.sitemap_discovery.max_sitemaps', 20));
        $maxUrls = max(1, (int) config('seo-audit.crawl.sitemap_discovery.max_urls', 1000));
        $includeQuery = (bool) config('seo-audit.crawl.sitemap_discovery.include_query', false);

        $queue = [];
        $queuedSitemaps = [];
        foreach ($seedPaths as $seedPath) {
            if (! is_string($seedPath) || trim($seedPath) === '') {
                continue;
            }

            $normalizedPath = $this->normalizePath($seedPath);
            $sitemapUrl = $baseUrl.$normalizedPath;
            $queue[] = $sitemapUrl;
            $queuedSitemaps[$sitemapUrl] = true;
        }

        $visitedSitemaps = [];
        $processedSitemaps = 0;
        $addedUrls = 0;

        while (
            $queue !== []
            && $processedSitemaps < $maxSitemaps
            && $addedUrls < $maxUrls
            && count($targetsByKey) < $maxPages
        ) {
            $sitemapUrl = array_shift($queue);
            if (! is_string($sitemapUrl) || isset($visitedSitemaps[$sitemapUrl])) {
                continue;
            }

            $visitedSitemaps[$sitemapUrl] = true;

            $xml = $this->fetchSitemapXml($sitemapUrl);
            if (! is_string($xml) || trim($xml) === '') {
                continue;
            }

            $processedSitemaps++;
            $entries = $this->parseSitemapEntries($xml);

            foreach ($entries['sitemaps'] as $childSitemapUrl) {
                if (! $this->isSameHost($childSitemapUrl, $baseUrl)) {
                    continue;
                }

                if (! isset($queuedSitemaps[$childSitemapUrl]) && ! isset($visitedSitemaps[$childSitemapUrl])) {
                    $queue[] = $childSitemapUrl;
                    $queuedSitemaps[$childSitemapUrl] = true;
                }
            }

            foreach ($entries['urls'] as $pageUrl) {
                $path = $this->normalizeUrlForTarget($pageUrl, $baseUrl, $sitemapUrl, $includeQuery, 'sitemap_discovery');
                if ($path === null) {
                    continue;
                }

                $candidate = new CrawlTarget(
                    url: $baseUrl.$path,
                    path: $path,
                    source: 'sitemap',
                );

                $key = $this->deduplicationKey($candidate->path, $candidate->url, $supportedLocales, $deduplicateLocalized);
                if (! isset($targetsByKey[$key])) {
                    $targetsByKey[$key] = $candidate;
                    $addedUrls++;
                }

                if ($addedUrls >= $maxUrls || count($targetsByKey) >= $maxPages) {
                    break;
                }
            }
        }

        return $targetsByKey;
    }

    protected function fetchSitemapXml(string $url): ?string
    {
        return $this->fetchUrlContents($url);
    }

    /**
     * @return array{sitemaps: array<int, string>, urls: array<int, string>}
     */
    private function parseSitemapEntries(string $xml): array
    {
        if (! class_exists(\SimpleXMLElement::class)) {
            return ['sitemaps' => [], 'urls' => []];
        }

        $internalErrors = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (! $document instanceof \SimpleXMLElement) {
            return ['sitemaps' => [], 'urls' => []];
        }

        $sitemaps = [];
        foreach (($document->xpath('/*[local-name()="sitemapindex"]/*[local-name()="sitemap"]/*[local-name()="loc"]') ?: []) as $node) {
            $loc = trim((string) $node);
            if ($loc !== '') {
                $sitemaps[] = $loc;
            }
        }

        $urls = [];
        foreach (($document->xpath('/*[local-name()="urlset"]/*[local-name()="url"]/*[local-name()="loc"]') ?: []) as $node) {
            $loc = trim((string) $node);
            if ($loc !== '') {
                $urls[] = $loc;
            }
        }

        return [
            'sitemaps' => array_values(array_unique($sitemaps)),
            'urls' => array_values(array_unique($urls)),
        ];
    }

    protected function fetchDiscoveryHtml(CrawlTarget $target): ?string
    {
        return $this->fetchUrlContents($target->url);
    }

    protected function fetchUrlContents(string $url): ?string
    {
        $contents = @file_get_contents($url);

        return $contents !== false ? $contents : null;
    }

    /**
     * @return array<int, string>
     */
    private function extractInternalPathsFromHtml(string $html, string $pageUrl, string $baseUrl): array
    {
        if (! class_exists(\DOMDocument::class)) {
            return [];
        }

        $document = new \DOMDocument;
        $internalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (! $loaded) {
            return [];
        }

        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//a[@href]');
        if ($nodes === false) {
            return [];
        }

        $paths = [];
        foreach ($nodes as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $href = trim((string) $node->getAttribute('href'));
            $normalizedPath = $this->normalizeDiscoveredHref(
                $href,
                $pageUrl,
                $baseUrl,
                (bool) config('seo-audit.crawl.link_discovery.include_query', false),
            );
            if ($normalizedPath === null) {
                continue;
            }

            $paths[$normalizedPath] = true;
        }

        return array_keys($paths);
    }

    private function normalizeDiscoveredHref(
        string $href,
        string $pageUrl,
        string $baseUrl,
        bool $includeQuery,
    ): ?string
    {
        if ($href === '' || str_starts_with($href, '#')) {
            return null;
        }

        $lowerHref = strtolower($href);
        if (
            str_starts_with($lowerHref, 'mailto:')
            || str_starts_with($lowerHref, 'tel:')
            || str_starts_with($lowerHref, 'javascript:')
            || str_starts_with($lowerHref, 'data:')
            || $this->isNonNavigationalRelativeHref($href)
        ) {
            return null;
        }

        return $this->normalizeUrlForTarget($href, $baseUrl, $pageUrl, $includeQuery, 'link_discovery');
    }

    private function resolveToAbsoluteUrl(string $href, string $pageUrl): ?string
    {
        $pageParts = parse_url($pageUrl);
        if ($pageParts === false) {
            return null;
        }

        $scheme = (string) ($pageParts['scheme'] ?? 'http');
        $host = (string) ($pageParts['host'] ?? '');
        if ($host === '') {
            return null;
        }

        $port = isset($pageParts['port']) ? ':'.$pageParts['port'] : '';
        $authority = $scheme.'://'.$host.$port;

        if (preg_match('/^https?:\/\//i', $href) === 1) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            return $scheme.':'.$href;
        }

        $hrefParts = parse_url($href);
        if ($hrefParts === false) {
            return null;
        }

        $hrefPath = (string) ($hrefParts['path'] ?? '');
        $hrefQuery = isset($hrefParts['query']) && $hrefParts['query'] !== '' ? '?'.$hrefParts['query'] : '';

        if (str_starts_with($href, '/')) {
            return $authority.$hrefPath.$hrefQuery;
        }

        $pagePath = (string) ($pageParts['path'] ?? '/');
        if ($hrefPath === '' && $hrefQuery !== '') {
            return $authority.$this->normalizePath($pagePath).$hrefQuery;
        }

        $baseDirectory = str_ends_with($pagePath, '/')
            ? $pagePath
            : dirname($pagePath);

        if ($baseDirectory === '\\' || $baseDirectory === '.') {
            $baseDirectory = '/';
        }

        $combined = rtrim($baseDirectory, '/').'/'.$hrefPath;
        $normalizedCombinedPath = $this->removeDotSegments($combined);

        return $authority.$normalizedCombinedPath.$hrefQuery;
    }

    private function isNonNavigationalRelativeHref(string $href): bool
    {
        if (preg_match('/\s/u', $href) === 1) {
            return true;
        }

        // Common invalid social handle link (missing absolute URL, e.g. "@example").
        if (str_starts_with($href, '@') && ! str_contains($href, '/')) {
            return true;
        }

        // Common invalid phone link where "tel:" is missing (e.g. "+98 912 000 0000").
        if (preg_match('/^\+[\d\-\s\(\)]+$/u', $href) === 1) {
            return true;
        }

        return false;
    }

    private function normalizeUrlForTarget(
        string $hrefOrUrl,
        string $baseUrl,
        string $contextUrl,
        bool $includeQuery,
        string $configScope,
    ): ?string {
        $absoluteUrl = $this->resolveToAbsoluteUrl($hrefOrUrl, $contextUrl);
        if ($absoluteUrl === null || ! $this->isSameHost($absoluteUrl, $baseUrl)) {
            return null;
        }

        $parts = parse_url($absoluteUrl);
        if ($parts === false) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '/');
        $path = $this->normalizePath($path);
        if ($path === '' || $path === '/index.php') {
            $path = '/';
        }

        if ($this->hasSkippedExtension($path, $configScope)) {
            return null;
        }

        if ($includeQuery && isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?'.$parts['query'];
        }

        return $path;
    }

    private function isSameHost(string $url, string $baseUrl): bool
    {
        $parts = parse_url($url);
        $baseParts = parse_url($baseUrl);
        if ($parts === false || $baseParts === false) {
            return false;
        }

        $targetHost = strtolower((string) ($parts['host'] ?? ''));
        $baseHost = strtolower((string) ($baseParts['host'] ?? ''));

        return $targetHost !== '' && $baseHost !== '' && $targetHost === $baseHost;
    }

    private function removeDotSegments(string $path): string
    {
        $segments = explode('/', $path);
        $output = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($output);

                continue;
            }

            $output[] = $segment;
        }

        return '/'.implode('/', $output);
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/'.ltrim($path, '/');
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        return $normalized !== '/' ? rtrim($normalized, '/') : '/';
    }

    private function hasSkippedExtension(string $path, string $configScope = 'link_discovery'): bool
    {
        $extensions = collect((array) config('seo-audit.crawl.'.$configScope.'.exclude_extensions', self::DEFAULT_SKIPPED_EXTENSIONS))
            ->filter(static fn ($ext): bool => is_string($ext) && $ext !== '')
            ->map(static fn (string $ext): string => strtolower(ltrim($ext, '.')))
            ->values();

        $extension = strtolower((string) pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));
        if ($extension === '') {
            return false;
        }

        return $extensions->contains($extension);
    }
}
