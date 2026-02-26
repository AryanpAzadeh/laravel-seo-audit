<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

class ContentSubheadingRule implements RuleInterface
{
    public function name(): string
    {
        return 'content_subheadings';
    }

    public function evaluate(SeoPageResult $page): array
    {
        $minWords = max(1, (int) config('seo-audit.content.min_words_for_subheadings', 350));
        if ($page->wordCount < $minWords) {
            return [];
        }

        if ($page->h2Count > 0) {
            return [];
        }

        return [
            new SeoIssue(
                rule: $this->name(),
                message: 'Long pages should include descriptive <h2> subheadings for better scanability.',
                severity: Severity::Warning,
                url: $page->url,
                context: [
                    'word_count' => $page->wordCount,
                    'h2_count' => $page->h2Count,
                    'recommendation' => 'Break the content into thematic sections and add clear H2 headings.',
                ],
            ),
        ];
    }
}
