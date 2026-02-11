<?php

use AryaAzadeh\LaravelSeoAudit\AuditRunner;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Data\SeoReport;
use AryaAzadeh\LaravelSeoAudit\Data\SeoRunSummary;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

it('outputs json and fails with code 2 when fail-on=error and errors exist', function (): void {
    $report = new SeoReport(
        reportVersion: '1.0.0',
        generatedAt: new \DateTimeImmutable(),
        summary: new SeoRunSummary(
            pages: 1,
            issues: 1,
            score: 90,
            info: 0,
            warning: 0,
            error: 1,
            critical: 0,
        ),
        pages: [
            new SeoPageResult(
                url: 'http://localhost/test',
                statusCode: 200,
                source: 'route',
                issues: [
                    new SeoIssue('title_exists', 'missing title', Severity::Error, 'http://localhost/test'),
                ],
            ),
        ],
    );

    $runner = \Mockery::mock(AuditRunner::class);
    $runner->shouldReceive('run')->once()->andReturn($report);
    app()->instance(AuditRunner::class, $runner);

    $this->artisan('seo:audit --format=json --fail-on=error')
        ->expectsOutputToContain('"report_version": "1.0.0"')
        ->assertExitCode(2);
});
