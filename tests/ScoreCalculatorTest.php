<?php

use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;
use AryaAzadeh\LaravelSeoAudit\Support\SeoScoreCalculator;

it('calculates overall technical and content scores separately', function (): void {
    $page = new SeoPageResult(
        url: 'http://localhost/page',
        statusCode: 200,
        source: 'route',
        issues: [
            new SeoIssue('title_exists', 'Missing title', Severity::Error, 'http://localhost/page'),
            new SeoIssue('content_word_count', 'Thin content', Severity::Warning, 'http://localhost/page'),
        ],
    );

    $calculator = new SeoScoreCalculator;

    expect($calculator->calculate([$page]))->toBe(88)
        ->and($calculator->calculateTechnical([$page]))->toBe(90)
        ->and($calculator->calculateContent([$page]))->toBe(98);
});
