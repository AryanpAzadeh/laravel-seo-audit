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
        public int $titleLength = 0,
        public int $metaDescriptionLength = 0,
        public int $h2Count = 0,
        public int $internalLinkCount = 0,
        public int $externalLinkCount = 0,
        public int $imagesCount = 0,
        public int $imagesWithoutAltCount = 0,
        public ?string $h1Text = null,
        public ?string $firstParagraph = null,
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
            'content_metrics' => [
                'title_length' => $this->titleLength,
                'meta_description_length' => $this->metaDescriptionLength,
                'h2_count' => $this->h2Count,
                'internal_links' => $this->internalLinkCount,
                'external_links' => $this->externalLinkCount,
                'images_count' => $this->imagesCount,
                'images_without_alt' => $this->imagesWithoutAltCount,
            ],
            'issues' => array_map(static fn (SeoIssue $issue): array => $issue->toArray(), $this->issues),
        ];
    }
}
