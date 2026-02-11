<?php

namespace AryaAzadeh\LaravelSeoAudit\Contracts;

use AryaAzadeh\LaravelSeoAudit\Data\CrawlTarget;

interface CrawlerInterface
{
    /** @return array<int, CrawlTarget> */
    public function crawl(int $maxPages = 100): array;
}
