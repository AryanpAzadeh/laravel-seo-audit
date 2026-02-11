<?php

namespace AryaAzadeh\LaravelSeoAudit\Contracts;

use AryaAzadeh\LaravelSeoAudit\Data\CrawlTarget;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;

interface AnalyzerInterface
{
    public function analyze(CrawlTarget $target, ?string $html, int $statusCode): SeoPageResult;
}
