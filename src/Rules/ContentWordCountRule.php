<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

class ContentWordCountRule implements RuleInterface
{
    public function name(): string
    {
        return 'content_word_count';
    }

    public function evaluate(SeoPageResult $page): array
    {
        $minWords = max(1, (int) config('seo-audit.content.min_word_count', 300));

        if ($page->wordCount >= $minWords) {
            return [];
        }

        return [
            new SeoIssue(
                rule: $this->name(),
                message: sprintf('Page content is thin (%d words). Minimum recommended is %d words.', $page->wordCount, $minWords),
                severity: Severity::Warning,
                url: $page->url,
                context: [
                    'word_count' => $page->wordCount,
                    'minimum_recommended' => $minWords,
                    'recommendation' => 'Expand the page with useful details, examples, FAQs, or comparison sections.',
                ],
            ),
        ];
    }
}
