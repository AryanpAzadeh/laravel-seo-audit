<?php

namespace AryaAzadeh\LaravelSeoAudit\Data;

final class SeoPageResult
{
    /** @param array<int, SeoIssue> $issues */
    public function __construct(
        public string $url,
        public int $statusCode,
        public string $source,
        public ?string $title = null,
        public ?string $metaDescription = null,
        public int $h1Count = 0,
        public int $wordCount = 0,
        public array $issues = [],
    ) {}

    public function withIssues(array $issues): self
    {
        $clone = clone $this;
        $clone->issues = $issues;

        return $clone;
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'status_code' => $this->statusCode,
            'source' => $this->source,
            'title' => $this->title,
            'meta_description' => $this->metaDescription,
            'h1_count' => $this->h1Count,
            'word_count' => $this->wordCount,
            'issues' => array_map(static fn (SeoIssue $issue): array => $issue->toArray(), $this->issues),
        ];
    }
}
