<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

class MetaDescriptionRule implements RuleInterface
{
    public function name(): string
    {
        return 'meta_description';
    }

    public function evaluate(SeoPageResult $page): array
    {
        if ($page->metaDescription !== null) {
            return [];
        }

        return [
            new SeoIssue(
                rule: $this->name(),
                message: 'Page is missing a meta description.',
                severity: Severity::Warning,
                url: $page->url,
            ),
        ];
    }
}
