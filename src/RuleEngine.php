<?php

namespace AryaAzadeh\LaravelSeoAudit;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;

class RuleEngine
{
    /** @param iterable<int, RuleInterface> $rules */
    public function __construct(private iterable $rules) {}

    public function evaluate(SeoPageResult $page): SeoPageResult
    {
        $issues = [];

        foreach ($this->rules as $rule) {
            foreach ($rule->evaluate($page) as $issue) {
                $issues[] = $issue;
            }
        }

        return $page->withIssues($issues);
    }
}
