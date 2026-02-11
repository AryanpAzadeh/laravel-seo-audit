<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

class SingleH1Rule implements RuleInterface
{
    public function name(): string
    {
        return 'single_h1';
    }

    public function evaluate(SeoPageResult $page): array
    {
        if ($page->h1Count === 1) {
            return [];
        }

        $severity = $page->h1Count === 0 ? Severity::Warning : Severity::Error;

        return [
            new SeoIssue(
                rule: $this->name(),
                message: 'Page should contain exactly one <h1> tag.',
                severity: $severity,
                url: $page->url,
                context: ['h1_count' => $page->h1Count],
            ),
        ];
    }
}
