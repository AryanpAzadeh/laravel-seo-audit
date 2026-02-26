<?php

namespace AryaAzadeh\LaravelSeoAudit\Support;

use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

class SeoScoreCalculator
{
    /** @param array<int, SeoPageResult> $pages */
    public function calculate(array $pages): int
    {
        return $this->calculateWithFilter($pages, static fn (): bool => true);
    }

    /** @param array<int, SeoPageResult> $pages */
    public function calculateTechnical(array $pages): int
    {
        return $this->calculateWithFilter(
            $pages,
            static fn (string $rule): bool => ! str_starts_with($rule, 'content_'),
        );
    }

    /** @param array<int, SeoPageResult> $pages */
    public function calculateContent(array $pages): int
    {
        return $this->calculateWithFilter(
            $pages,
            static fn (string $rule): bool => str_starts_with($rule, 'content_'),
        );
    }

    /**
     * @param array<int, SeoPageResult> $pages
     * @param callable(string):bool $ruleFilter
     */
    private function calculateWithFilter(array $pages, callable $ruleFilter): int
    {
        $deduction = 0;

        foreach ($pages as $page) {
            foreach ($page->issues as $issue) {
                if (! $ruleFilter($issue->rule)) {
                    continue;
                }

                $deduction += Severity::weight($issue->severity);
            }
        }

        return max(0, 100 - $deduction);
    }
}
