<?php

use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\RuleEngine;
use AryaAzadeh\LaravelSeoAudit\Rules\ContentFocusKeywordRule;
use AryaAzadeh\LaravelSeoAudit\Rules\ContentImageAltRule;
use AryaAzadeh\LaravelSeoAudit\Rules\ContentInternalLinksRule;
use AryaAzadeh\LaravelSeoAudit\Rules\ContentMetaDescriptionQualityRule;
use AryaAzadeh\LaravelSeoAudit\Rules\ContentSubheadingRule;
use AryaAzadeh\LaravelSeoAudit\Rules\ContentTitleQualityRule;
use AryaAzadeh\LaravelSeoAudit\Rules\ContentWordCountRule;
use AryaAzadeh\LaravelSeoAudit\Rules\MetaDescriptionRule;
use AryaAzadeh\LaravelSeoAudit\Rules\TitleExistsRule;

it('evaluates content quality rules and adds actionable suggestions', function (): void {
    config()->set('seo-audit.content.title.min', 30);
    config()->set('seo-audit.content.title.max', 60);
    config()->set('seo-audit.content.meta_description.min', 120);
    config()->set('seo-audit.content.meta_description.max', 160);
    config()->set('seo-audit.content.min_word_count', 500);
    config()->set('seo-audit.content.min_words_for_subheadings', 350);
    config()->set('seo-audit.content.min_words_for_internal_links', 250);
    config()->set('seo-audit.content.min_internal_links', 2);
    config()->set('seo-audit.content.focus_keywords', [
        '/content-test' => 'seo audit',
    ]);

    $engine = new RuleEngine([
        new ContentTitleQualityRule,
        new ContentMetaDescriptionQualityRule,
        new ContentWordCountRule,
        new ContentSubheadingRule,
        new ContentImageAltRule,
        new ContentInternalLinksRule,
        new ContentFocusKeywordRule,
    ]);

    $page = new SeoPageResult(
        url: 'http://localhost/content-test',
        statusCode: 200,
        source: 'route',
        title: 'Short',
        metaDescription: 'Too short',
        h1Count: 1,
        wordCount: 420,
        titleLength: 5,
        metaDescriptionLength: 9,
        h2Count: 0,
        internalLinkCount: 0,
        externalLinkCount: 1,
        imagesCount: 3,
        imagesWithoutAltCount: 2,
        h1Text: 'Guide',
        firstParagraph: 'This introduction does not include the focus term.',
    );

    $evaluated = $engine->evaluate($page);
    $issueMap = collect($evaluated->issues)->keyBy('rule');

    expect($evaluated->issues)->toHaveCount(7)
        ->and($issueMap->keys()->all())->toMatchArray([
            'content_title_quality',
            'content_meta_description_quality',
            'content_word_count',
            'content_subheadings',
            'content_image_alt',
            'content_internal_links',
            'content_focus_keyword',
        ])
        ->and(data_get($issueMap->get('content_title_quality')->context, 'suggested_title'))->toBeString()
        ->and(data_get($issueMap->get('content_meta_description_quality')->context, 'suggested_meta_description'))->toBeString()
        ->and(data_get($issueMap->get('content_focus_keyword')->context, 'missing_in'))->toContain('title');
});

it('includes deterministic title/meta suggestions for missing tags', function (): void {
    config()->set('seo-audit.content.site_name', 'Coolak');

    $engine = new RuleEngine([
        new TitleExistsRule,
        new MetaDescriptionRule,
    ]);

    $page = new SeoPageResult(
        url: 'http://localhost/missing-head',
        statusCode: 200,
        source: 'route',
        title: null,
        metaDescription: null,
        h1Count: 1,
        wordCount: 250,
        h1Text: 'Missing Head Example',
        firstParagraph: 'This paragraph provides enough context for a meta suggestion.',
    );

    $evaluated = $engine->evaluate($page);
    $issueMap = collect($evaluated->issues)->keyBy('rule');

    expect($evaluated->issues)->toHaveCount(2)
        ->and(data_get($issueMap->get('title_exists')->context, 'suggested_title'))->toContain('Missing Head Example')
        ->and(data_get($issueMap->get('meta_description')->context, 'suggested_meta_description'))->toBeString();
});
