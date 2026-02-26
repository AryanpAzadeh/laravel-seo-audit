<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;
use AryaAzadeh\LaravelSeoAudit\Support\ContentSuggestionBuilder;
use AryaAzadeh\LaravelSeoAudit\Support\FocusKeywordResolver;

class ContentTitleQualityRule implements RuleInterface
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
        return 'content_title_quality';
    }

    public function evaluate(SeoPageResult $page): array
    {
        if ($page->title === null || trim($page->title) === '') {
            return [];
        }

        $min = max(1, (int) config('seo-audit.content.title.min', 30));
        $max = max($min, (int) config('seo-audit.content.title.max', 60));
        $length = $page->titleLength > 0 ? $page->titleLength : mb_strlen((string) $page->title);

        if ($length >= $min && $length <= $max) {
            return [];
        }

        $focusKeyword = $this->keywordResolver->resolveForUrl($page->url);
        $suggestedTitle = $this->suggestionBuilder->suggestTitle($page, $focusKeyword);

        return [
            new SeoIssue(
                rule: $this->name(),
                message: sprintf('Title length should be between %d and %d characters.', $min, $max),
                severity: Severity::Warning,
                url: $page->url,
                context: [
                    'title_length' => $length,
                    'recommended_range' => [$min, $max],
                    'recommendation' => 'Adjust title length and keep the primary topic near the beginning.',
                    'suggested_title' => $suggestedTitle,
                ],
            ),
        ];
    }
}
