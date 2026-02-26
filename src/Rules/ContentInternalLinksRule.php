<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

class ContentInternalLinksRule implements RuleInterface
{
    public function name(): string
    {
        return 'content_internal_links';
    }

    public function evaluate(SeoPageResult $page): array
    {
        $minWords = max(1, (int) config('seo-audit.content.min_words_for_internal_links', 250));
        $minLinks = max(0, (int) config('seo-audit.content.min_internal_links', 2));

        if ($page->wordCount < $minWords || $page->internalLinkCount >= $minLinks) {
            return [];
        }

        return [
            new SeoIssue(
                rule: $this->name(),
                message: sprintf('Page has low internal linking (%d internal links).', $page->internalLinkCount),
                severity: Severity::Warning,
                url: $page->url,
                context: [
                    'internal_links' => $page->internalLinkCount,
                    'minimum_recommended' => $minLinks,
                    'recommendation' => 'Link to related pages to improve crawl paths and topical context.',
                ],
            ),
        ];
    }
}
