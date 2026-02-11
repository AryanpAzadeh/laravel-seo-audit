<?php

namespace AryaAzadeh\LaravelSeoAudit\Data;

final class SeoRunSummary
{
    public function __construct(
        public int $pages,
        public int $issues,
        public int $score,
        public int $info,
        public int $warning,
        public int $error,
        public int $critical,
    ) {}

    public function toArray(): array
    {
        return [
            'pages' => $this->pages,
            'issues' => $this->issues,
            'score' => $this->score,
            'severity' => [
                'info' => $this->info,
                'warning' => $this->warning,
                'error' => $this->error,
                'critical' => $this->critical,
            ],
        ];
    }
}
