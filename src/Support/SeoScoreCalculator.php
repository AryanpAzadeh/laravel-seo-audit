<?php

namespace AryaAzadeh\LaravelSeoAudit\Support;

use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

class SeoScoreCalculator
{
    /** @param array<int, SeoPageResult> $pages */
    public function calculate(array $pages): int
    {
        $deduction = 0;

        foreach ($pages as $page) {
            foreach ($page->issues as $issue) {
                $deduction += Severity::weight($issue->severity);
            }
        }

        return max(0, 100 - $deduction);
    }
}
