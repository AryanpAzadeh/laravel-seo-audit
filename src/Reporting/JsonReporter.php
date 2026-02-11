<?php

namespace AryaAzadeh\LaravelSeoAudit\Reporting;

use AryaAzadeh\LaravelSeoAudit\Contracts\ReporterInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoReport;

class JsonReporter implements ReporterInterface
{
    public function render(SeoReport $report): string
    {
        return (string) json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
