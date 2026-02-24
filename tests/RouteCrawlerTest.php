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
