<?php

namespace AryaAzadeh\LaravelSeoAudit\Contracts;

use AryaAzadeh\LaravelSeoAudit\Data\SeoReport;

interface ReporterInterface
{
    public function render(SeoReport $report): string;
}
