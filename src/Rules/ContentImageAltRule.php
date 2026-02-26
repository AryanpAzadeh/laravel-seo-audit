<?php

namespace AryaAzadeh\LaravelSeoAudit\Rules;

use AryaAzadeh\LaravelSeoAudit\Contracts\RuleInterface;
use AryaAzadeh\LaravelSeoAudit\Data\SeoIssue;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

class ContentImageAltRule implements RuleInterface
{
    public function name(): string
    {
        return 'content_image_alt';
    }

    public function evaluate(SeoPageResult $page): array
    {
        if ($page->imagesCount === 0 || $page->imagesWithoutAltCount === 0) {
            return [];
        }

        return [
            new SeoIssue(
                rule: $this->name(),
                message: sprintf('%d image(s) are missing alt text.', $page->imagesWithoutAltCount),
                severity: Severity::Warning,
                url: $page->url,
                context: [
                    'images_count' => $page->imagesCount,
                    'images_without_alt' => $page->imagesWithoutAltCount,
                    'recommendation' => 'Add concise, descriptive alt text for informative images.',
                ],
            ),
        ];
    }
}
