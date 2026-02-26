<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;
use AryaAzadeh\LaravelSeoAudit\Support\ContentSuggestionBuilder;
use AryaAzadeh\LaravelSeoAudit\Support\FocusKeywordResolver;

class ContentMetaDescriptionQualityRule implements RuleInterface
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
        return 'content_meta_description_quality';
    }

    public function evaluate(SeoPageResult $page): array
    {
        if ($page->metaDescription === null || trim($page->metaDescription) === '') {
            return [];
        }

        $min = max(1, (int) config('seo-audit.content.meta_description.min', 120));
        $max = max($min, (int) config('seo-audit.content.meta_description.max', 160));
        $length = $page->metaDescriptionLength > 0 ? $page->metaDescriptionLength : mb_strlen((string) $page->metaDescription);

        if ($length >= $min && $length <= $max) {
            return [];
        }

        $focusKeyword = $this->keywordResolver->resolveForUrl($page->url);
        $suggested = $this->suggestionBuilder->suggestMetaDescription($page, $focusKeyword);

        return [
            new SeoIssue(
                rule: $this->name(),
                message: sprintf('Meta description length should be between %d and %d characters.', $min, $max),
                severity: Severity::Warning,
                url: $page->url,
                context: [
                    'meta_description_length' => $length,
                    'recommended_range' => [$min, $max],
                    'recommendation' => 'Use a concise summary with intent and a clear value proposition.',
                    'suggested_meta_description' => $suggested,
                ],
            ),
        ];
    }
}
