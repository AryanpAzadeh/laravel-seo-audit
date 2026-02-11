<?php

use AryaAzadeh\LaravelSeoAudit\Crawling\RouteCrawler;
use Illuminate\Routing\Router;

it('falls back to APP_URL when no crawlable routes are found', function (): void {
    config()->set('app.url', 'http://example.test');
    config()->set('seo-audit.crawl.http_fallback', true);

    $router = new Router(app('events'), app());
    $crawler = new RouteCrawler($router);

    $targets = $crawler->crawl(10);

    expect($targets)->toHaveCount(1)
        ->and($targets[0]->source)->toBe('http-fallback')
        ->and($targets[0]->url)->toBe('http://example.test');
});
