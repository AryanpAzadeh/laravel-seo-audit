<?php

namespace AryaAzadeh\LaravelSeoAudit\Contracts;

use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;

interface RuleInterface
{
    /** @return array<int, \AryaAzadeh\LaravelSeoAudit\Data\SeoIssue> */
    public function evaluate(SeoPageResult $page): array;

    public function name(): string;
}
