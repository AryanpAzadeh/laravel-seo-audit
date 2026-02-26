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
        public int $technicalScore = 100,
        public int $contentScore = 100,
    ) {}

    public function toArray(): array
    {
        return [
            'pages' => $this->pages,
            'issues' => $this->issues,
            'score' => $this->score,
            'technical_score' => $this->technicalScore,
            'content_score' => $this->contentScore,
            'severity' => [
                'info' => $this->info,
                'warning' => $this->warning,
                'error' => $this->error,
                'critical' => $this->critical,
            ],
        ];
    }
}
