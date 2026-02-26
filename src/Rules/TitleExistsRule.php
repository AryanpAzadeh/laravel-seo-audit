<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;
use AryaAzadeh\LaravelSeoAudit\Support\ContentSuggestionBuilder;
use AryaAzadeh\LaravelSeoAudit\Support\FocusKeywordResolver;

class TitleExistsRule implements RuleInterface
{
    public function __construct(
        private ?ContentSuggestionBuilder $suggestionBuilder = null,
        private ?FocusKeywordResolver $keywordResolver = null,
    ) {
        $this->suggestionBuilder ??= new ContentSuggestionBuilder;
        $this->keywordResolver ??= new FocusKeywordResolver;
    }

    public function name(): string
    {
        return 'title_exists';
    }

    public function evaluate(SeoPageResult $page): array
    {
        if ($page->title !== null) {
            return [];
        }

        return [
            new SeoIssue(
                rule: $this->name(),
                message: 'Page is missing a <title> tag.',
                severity: Severity::Error,
                url: $page->url,
                context: [
                    'recommendation' => 'Add a unique title that summarizes the page intent.',
                    'suggested_title' => $this->suggestionBuilder->suggestTitle(
                        $page,
                        $this->keywordResolver->resolveForUrl($page->url),
                    ),
                ],
            ),
        ];
    }
}
