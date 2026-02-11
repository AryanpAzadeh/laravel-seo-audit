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
