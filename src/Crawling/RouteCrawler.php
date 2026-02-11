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
        $targets = [];
        $baseUrl = rtrim((string) config('app.url', 'http://localhost'), '/');
        $blockList = ['auth', 'verified', 'password.confirm', 'signed'];

        foreach ($this->router->getRoutes() as $route) {
            $methods = $route->methods();

            if (! in_array('GET', $methods, true)) {
                continue;
            }

            $middlewares = $route->gatherMiddleware();
            $isBlocked = collect($middlewares)->contains(static function (string $middleware) use ($blockList): bool {
                if (str_starts_with($middleware, 'can:')) {
                    return true;
                }

                return in_array($middleware, $blockList, true) || str_starts_with($middleware, 'auth');
            });

            if ($isBlocked || ! in_array('web', $middlewares, true)) {
                continue;
            }

            $path = '/'.ltrim($route->uri(), '/');
            $targets[] = new CrawlTarget(
                url: $baseUrl.$path,
                path: $path,
                source: 'route',
                routeName: $route->getName(),
            );

            if (count($targets) >= $maxPages) {
                break;
            }
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
}
