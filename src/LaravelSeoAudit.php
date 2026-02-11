<?php

namespace AryaAzadeh\LaravelSeoAudit;

use AryaAzadeh\LaravelSeoAudit\Data\SeoReport;

class LaravelSeoAudit
{
    public function __construct(private AuditRunner $runner) {}

    public function audit(int $maxPages = 100): SeoReport
    {
        return $this->runner->run($maxPages);
    }
}
