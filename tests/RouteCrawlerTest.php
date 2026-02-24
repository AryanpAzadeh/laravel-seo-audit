<?php

use AryaAzadeh\LaravelSeoAudit\Crawling\RouteCrawler;
use Illuminate\Support\Facades\Route;

it('crawls only public GET web routes', function (): void {
    Route::middleware('web')->get('/public-page', fn () => 'ok');
    Route::middleware(['web', 'auth'])->get('/private-page', fn () => 'private');

    $targets = app(RouteCrawler::class)->crawl(50);
    $paths = array_map(static fn ($target): string => $target->path, $targets);

    expect($paths)->toContain('/public-page')
        ->and($paths)->not->toContain('/private-page');
});

it('skips parameterized routes and deduplicates localized routes', function (): void {
    config()->set('seo-audit.crawl.exclude_parameterized_routes', true);
    config()->set('seo-audit.crawl.deduplicate_localized_routes', true);
    config()->set('app.locale', 'fa');
    config()->set('laravellocalization.supportedLocales', [
        'fa' => ['name' => 'Farsi'],
        'en' => ['name' => 'English'],
    ]);

    Route::middleware('web')->get('/products/{slug}', fn () => 'product');
    Route::middleware('web')->get('/fa/about-us', fn () => 'about fa');
    Route::middleware('web')->get('/en/about-us', fn () => 'about en');
    Route::middleware('web')->get('/about-us', fn () => 'about');

    $targets = app(RouteCrawler::class)->crawl(50);
    $paths = array_map(static fn ($target): string => $target->path, $targets);

    expect($paths)->not->toContain('/products/{slug}')
        ->and($paths)->toContain('/fa/about-us')
        ->and($paths)->not->toContain('/about-us')
        ->and(collect($paths)->filter(static fn (string $path): bool => str_ends_with($path, '/about-us'))->count())->toBe(1);
});

it('applies active locale prefix for localized middleware routes', function (): void {
    config()->set('seo-audit.crawl.deduplicate_localized_routes', true);
    config()->set('app.locale', 'fa');
    config()->set('laravellocalization.supportedLocales', [
        'fa' => ['name' => 'Farsi'],
        'en' => ['name' => 'English'],
    ]);

    Route::middleware(['web', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath'])
        ->get('/products', fn () => 'products');

    $targets = app(RouteCrawler::class)->crawl(50);
    $paths = array_map(static fn ($target): string => $target->path, $targets);

    expect($paths)->toContain('/fa/products')
        ->and($paths)->not->toContain('/products');
});

it('discovers additional internal linked pages when link discovery is enabled', function (): void {
    config()->set('app.url', 'http://example.test');
    config()->set('seo-audit.crawl.exclude_parameterized_routes', true);
    config()->set('seo-audit.crawl.link_discovery.enabled', true);
    config()->set('seo-audit.crawl.link_discovery.seed_from_route_targets', true);
    config()->set('seo-audit.crawl.link_discovery.max_pages', 10);

    Route::middleware('web')->get('/blog', fn () => 'blog list');
    Route::middleware('web')->get('/blog/{slug}', fn (string $slug) => $slug);

    $crawler = new class(app('router')) extends RouteCrawler
    {
        protected function fetchDiscoveryHtml(\AryaAzadeh\LaravelSeoAudit\Data\CrawlTarget $target): ?string
        {
            return match ($target->path) {
                '/blog' => <<<'HTML'
                    <a href="/blog/post-1">Post 1</a>
                    <a href="http://example.test/blog/post-2?utm=abc#top">Post 2</a>
                    <a href="https://google.com/blog/external">External</a>
                    HTML,
                default => null,
            };
        }
    };

    $targets = $crawler->crawl(50);
    $paths = array_map(static fn ($target): string => $target->path, $targets);
    $targetsByPath = collect($targets)->keyBy(static fn ($target): string => $target->path);

    expect($paths)->toContain('/blog')
        ->and($paths)->toContain('/blog/post-1')
        ->and($paths)->toContain('/blog/post-2')
        ->and($paths)->not->toContain('/blog/{slug}')
        ->and($targetsByPath->get('/blog/post-1')->source)->toBe('discovered-link')
        ->and($targetsByPath->get('/blog/post-2')->source)->toBe('discovered-link');
});

it('discovers internal pages from sitemap files when sitemap discovery is enabled', function (): void {
    config()->set('app.url', 'http://example.test');
    config()->set('seo-audit.crawl.http_fallback', false);
    config()->set('seo-audit.crawl.sitemap_discovery.enabled', true);
    config()->set('seo-audit.crawl.sitemap_discovery.seed_paths', ['/sitemap.xml']);
    config()->set('seo-audit.crawl.sitemap_discovery.max_sitemaps', 5);
    config()->set('seo-audit.crawl.sitemap_discovery.max_urls', 10);
    config()->set('seo-audit.crawl.sitemap_discovery.include_query', false);

    Route::middleware('web')->get('/blog', fn () => 'blog');

    $crawler = new class(app('router')) extends RouteCrawler
    {
        protected function fetchSitemapXml(string $url): ?string
        {
            return match ($url) {
                'http://example.test/sitemap.xml' => <<<'XML'
                    <?xml version="1.0" encoding="UTF-8"?>
                    <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                        <sitemap><loc>http://example.test/sitemaps/posts.xml</loc></sitemap>
                    </sitemapindex>
                    XML,
                'http://example.test/sitemaps/posts.xml' => <<<'XML'
                    <?xml version="1.0" encoding="UTF-8"?>
                    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                        <url><loc>http://example.test/blog/post-1</loc></url>
                        <url><loc>http://example.test/blog/post-2?utm=ad</loc></url>
                        <url><loc>https://external.test/blog/post-3</loc></url>
                        <url><loc>http://example.test/storage/seo-report.pdf</loc></url>
                    </urlset>
                    XML,
                default => null,
            };
        }
    };

    $targets = $crawler->crawl(50);
    $paths = array_map(static fn ($target): string => $target->path, $targets);
    $targetsByPath = collect($targets)->keyBy(static fn ($target): string => $target->path);

    expect($paths)->toContain('/blog')
        ->and($paths)->toContain('/blog/post-1')
        ->and($paths)->toContain('/blog/post-2')
        ->and($paths)->not->toContain('/storage/seo-report.pdf')
        ->and($targetsByPath->get('/blog/post-1')->source)->toBe('sitemap')
        ->and($targetsByPath->get('/blog/post-2')->source)->toBe('sitemap');
});
