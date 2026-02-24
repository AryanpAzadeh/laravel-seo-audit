<?php

namespace AryaAzadeh\LaravelSeoAudit\Crawling;

use AryaAzadeh\LaravelSeoAudit\Contracts\CrawlerInterface;
use AryaAzadeh\LaravelSeoAudit\Data\CrawlTarget;
use Illuminate\Routing\Router;

class RouteCrawler implements CrawlerInterface
{
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
            if ($this->isLocalizedPath($existing->path, $supportedLocales) && ! $this->isLocalizedPath($candidate->path, $supportedLocales)) {
                $targetsByKey[$key] = $candidate;
            }
        }

        $targets = array_values($targetsByKey);
        if (count($targets) > $maxPages) {
            $targets = array_slice($targets, 0, $maxPages);
        }

        if ($targets === [] && (bool) config('seo-audit.crawl.http_fallback', true)) {
            $targets[] = new CrawlTarget(
                url: $baseUrl,
                path: '/',
                source: 'http-fallback',
            );
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

    private function isLocalizedPath(string $path, array $supportedLocales): bool
    {
        $firstSegment = strtok(trim($path, '/'), '/');

        return is_string($firstSegment) && $firstSegment !== '' && in_array($firstSegment, $supportedLocales, true);
    }
}
