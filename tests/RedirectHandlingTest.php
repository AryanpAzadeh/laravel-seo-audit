<?php

use AryaAzadeh\LaravelSeoAudit\Analysis\HtmlAnalyzer;
use AryaAzadeh\LaravelSeoAudit\AuditRunner;
use AryaAzadeh\LaravelSeoAudit\Contracts\CrawlerInterface;
use AryaAzadeh\LaravelSeoAudit\Data\CrawlTarget;
use AryaAzadeh\LaravelSeoAudit\RuleEngine;
use AryaAzadeh\LaravelSeoAudit\Rules\MetaDescriptionRule;
use AryaAzadeh\LaravelSeoAudit\Rules\SingleH1Rule;
use AryaAzadeh\LaravelSeoAudit\Rules\TitleExistsRule;
use AryaAzadeh\LaravelSeoAudit\Support\SeoScoreCalculator;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;

it('follows internal route redirects before applying seo rules', function (): void {
    $crawler = new class implements CrawlerInterface
    {
        public function crawl(int $maxPages = 100): array
        {
            return [new CrawlTarget('http://localhost/products', '/products', 'route')];
        }
    };

    Route::middleware('web')->get('/products', static fn () => redirect('/fa/products'));
    Route::middleware('web')->get('/fa/products', static fn () => <<<'HTML'
        <!doctype html>
        <html lang="fa">
        <head>
            <title>Products</title>
            <meta name="description" content="Products listing page for localized website">
        </head>
        <body>
            <h1>محصولات</h1>
        </body>
        </html>
        HTML);

    $runner = new AuditRunner(
        $crawler,
        new HtmlAnalyzer,
        new RuleEngine([
            new TitleExistsRule,
            new MetaDescriptionRule,
            new SingleH1Rule,
        ]),
        new SeoScoreCalculator,
        app(Kernel::class),
    );

    $report = $runner->run(10);

    expect($report->summary->issues)->toBe(0)
        ->and($report->summary->warning)->toBe(0)
        ->and($report->summary->error)->toBe(0)
        ->and($report->pages)->toHaveCount(1)
        ->and($report->pages[0]->statusCode)->toBe(200)
        ->and($report->pages[0]->issues)->toBe([]);
});
