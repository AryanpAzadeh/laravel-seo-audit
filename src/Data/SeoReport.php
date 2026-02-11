<?php

namespace AryaAzadeh\LaravelSeoAudit\Data;

use DateTimeImmutable;

final class SeoReport
{
    /** @param array<int, SeoPageResult> $pages */
    public function __construct(
        public string $reportVersion,
        public DateTimeImmutable $generatedAt,
        public SeoRunSummary $summary,
        public array $pages,
    ) {}

    public function toArray(): array
    {
        return [
            'report_version' => $this->reportVersion,
            'generated_at' => $this->generatedAt->format(DATE_ATOM),
            'summary' => $this->summary->toArray(),
            'pages' => array_map(static fn (SeoPageResult $page): array => $page->toArray(), $this->pages),
        ];
    }
}
