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

it('falls back to http content when route source returns 404 in cli context', function (): void {
    config()->set('seo-audit.crawl.route_http_fallback_on_error', true);
    config()->set('laravellocalization.supportedLocales', []);

    $crawler = new class implements CrawlerInterface
    {
        public function crawl(int $maxPages = 100): array
        {
            return [new CrawlTarget('http://coolak.test/fa/products', '/fa/products', 'route')];
        }
    };

    $runner = new class(
        $crawler,
        new HtmlAnalyzer,
        new RuleEngine([
            new TitleExistsRule,
            new MetaDescriptionRule,
            new SingleH1Rule,
        ]),
        new SeoScoreCalculator,
        app(Kernel::class),
    ) extends AuditRunner {
        protected function fetchHttpContent(string $url): array
        {
            expect($url)->toBe('http://coolak.test/fa/products');

            return [<<<'HTML'
                <!doctype html>
                <html lang="fa">
                <head>
                    <title>Products - Coolak</title>
                    <meta name="description" content="The Modern Standard of Industrial Material Supply">
                </head>
                <body>
                    <h1>Products</h1>
                </body>
                </html>
                HTML, 200];
        }
    };

    $report = $runner->run(10);

    expect($report->summary->issues)->toBe(0)
        ->and($report->pages)->toHaveCount(1)
        ->and($report->pages[0]->statusCode)->toBe(200)
        ->and($report->pages[0]->title)->toBe('Products - Coolak')
        ->and($report->pages[0]->metaDescription)->toBe('The Modern Standard of Industrial Material Supply');
});
