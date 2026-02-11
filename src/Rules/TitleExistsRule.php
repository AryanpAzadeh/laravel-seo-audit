<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

class TitleExistsRule implements RuleInterface
{
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
            ),
        ];
    }
}
