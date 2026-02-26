<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;
use AryaAzadeh\LaravelSeoAudit\Support\ContentSuggestionBuilder;
use AryaAzadeh\LaravelSeoAudit\Support\FocusKeywordResolver;

class ContentFocusKeywordRule implements RuleInterface
{
    public function __construct(
        private ?FocusKeywordResolver $keywordResolver = null,
        private ?ContentSuggestionBuilder $suggestionBuilder = null,
    ) {
        $this->keywordResolver ??= new FocusKeywordResolver;
        $this->suggestionBuilder ??= new ContentSuggestionBuilder;
    }

    public function name(): string
    {
        return 'content_focus_keyword';
    }

    public function evaluate(SeoPageResult $page): array
    {
        $focusKeyword = $this->keywordResolver->resolveForUrl($page->url);
        if ($focusKeyword === null || $focusKeyword === '') {
            return [];
        }

        $missing = [];

        if (! $this->contains($page->title, $focusKeyword)) {
            $missing[] = 'title';
        }

        if (! $this->contains($page->metaDescription, $focusKeyword)) {
            $missing[] = 'meta_description';
        }

        if (! $this->contains($page->h1Text, $focusKeyword)) {
            $missing[] = 'h1';
        }

        if (! $this->contains($page->firstParagraph, $focusKeyword)) {
            $missing[] = 'first_paragraph';
        }

        if ($missing === []) {
            return [];
        }

        return [
            new SeoIssue(
                rule: $this->name(),
                message: sprintf('Focus keyword "%s" is missing from key sections.', $focusKeyword),
                severity: Severity::Warning,
                url: $page->url,
                context: [
                    'focus_keyword' => $focusKeyword,
                    'missing_in' => $missing,
                    'recommendation' => 'Include the focus keyword naturally in title, meta, H1, and opening paragraph.',
                    'suggested_title' => $this->suggestionBuilder->suggestTitle($page, $focusKeyword),
                    'suggested_meta_description' => $this->suggestionBuilder->suggestMetaDescription($page, $focusKeyword),
                ],
            ),
        ];
    }

    private function contains(?string $content, string $keyword): bool
    {
        if ($content === null || trim($content) === '') {
            return false;
        }

        return mb_stripos($content, $keyword) !== false;
    }
}
