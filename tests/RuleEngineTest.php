<?php

use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\RuleEngine;
use AryaAzadeh\LaravelSeoAudit\Rules\MetaDescriptionRule;
use AryaAzadeh\LaravelSeoAudit\Rules\SingleH1Rule;
use AryaAzadeh\LaravelSeoAudit\Rules\TitleExistsRule;

it('aggregates rule issues with expected severities', function (): void {
    $engine = new RuleEngine([
        new TitleExistsRule,
        new MetaDescriptionRule,
        new SingleH1Rule,
    ]);

    $page = new SeoPageResult(
        url: 'http://localhost/page',
        statusCode: 200,
        source: 'route',
        title: null,
        metaDescription: null,
        h1Count: 0,
        wordCount: 150,
    );

    $evaluated = $engine->evaluate($page);

    expect($evaluated->issues)->toHaveCount(3)
        ->and(collect($evaluated->issues)->pluck('severity.value')->all())
        ->toMatchArray(['error', 'warning', 'warning']);
});
