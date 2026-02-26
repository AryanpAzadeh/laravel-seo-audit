<?php

namespace AryaAzadeh\LaravelSeoAudit\Analysis;

use AryaAzadeh\LaravelSeoAudit\Contracts\AnalyzerInterface;
use AryaAzadeh\LaravelSeoAudit\Data\CrawlTarget;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use DOMDocument;
use DOMElement;
use DOMXPath;

class HtmlAnalyzer implements AnalyzerInterface
{
    public function analyze(CrawlTarget $target, ?string $html, int $statusCode): SeoPageResult
    {
        if ($html === null || trim($html) === '') {
            return new SeoPageResult(
                url: $target->url,
                statusCode: $statusCode,
                source: $target->source,
            );
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $title = trim((string) $xpath->evaluate('string(//title)'));
        $metaDescription = trim((string) $xpath->evaluate("string(//meta[@name='description']/@content)"));
        $h1Count = (int) $xpath->evaluate('count(//h1)');
        $h2Count = (int) $xpath->evaluate('count(//h2)');
        $h1Text = trim((string) $xpath->evaluate('string((//h1)[1])'));
        $firstParagraph = trim((string) $xpath->evaluate('string((//p)[1])'));
        $imagesCount = (int) $xpath->evaluate('count(//img)');
        $imagesWithoutAltCount = (int) $xpath->evaluate('count(//img[not(@alt) or normalize-space(@alt)=""])');
        [$internalLinks, $externalLinks] = $this->extractLinkCounts($xpath, $target->url);
        $text = trim((string) preg_replace('/\s+/', ' ', (string) $dom->textContent));

        return new SeoPageResult(
            url: $target->url,
            statusCode: $statusCode,
            source: $target->source,
            title: $title !== '' ? $title : null,
            metaDescription: $metaDescription !== '' ? $metaDescription : null,
            h1Count: $h1Count,
            wordCount: $text === '' ? 0 : count(explode(' ', $text)),
            titleLength: $title !== '' ? mb_strlen($title) : 0,
            metaDescriptionLength: $metaDescription !== '' ? mb_strlen($metaDescription) : 0,
            h2Count: $h2Count,
            internalLinkCount: $internalLinks,
            externalLinkCount: $externalLinks,
            imagesCount: $imagesCount,
            imagesWithoutAltCount: $imagesWithoutAltCount,
            h1Text: $h1Text !== '' ? $h1Text : null,
            firstParagraph: $firstParagraph !== '' ? $firstParagraph : null,
        );
    }

    /** @return array{0: int, 1: int} */
    private function extractLinkCounts(DOMXPath $xpath, string $pageUrl): array
    {
        $nodes = $xpath->query('//a[@href]');
        if ($nodes === false) {
            return [0, 0];
        }

        $internal = 0;
        $external = 0;
        $pageHost = strtolower((string) (parse_url($pageUrl, PHP_URL_HOST) ?? ''));
        $pageScheme = strtolower((string) (parse_url($pageUrl, PHP_URL_SCHEME) ?? 'http'));

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $href = trim((string) $node->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#')) {
                continue;
            }

            $lowerHref = strtolower($href);
            if (
                str_starts_with($lowerHref, 'mailto:')
                || str_starts_with($lowerHref, 'tel:')
                || str_starts_with($lowerHref, 'javascript:')
                || str_starts_with($lowerHref, 'data:')
            ) {
                continue;
            }

            if (str_starts_with($href, '/')) {
                $internal++;

                continue;
            }

            if (str_starts_with($href, '//')) {
                $href = $pageScheme.':'.$href;
            }

            if (preg_match('/^https?:\/\//i', $href) === 1) {
                $linkHost = strtolower((string) (parse_url($href, PHP_URL_HOST) ?? ''));
                if ($linkHost !== '' && $pageHost !== '' && $linkHost === $pageHost) {
                    $internal++;
                } else {
                    $external++;
                }

                continue;
            }

            // Relative hrefs are internal.
            $internal++;
        }

        return [$internal, $external];
    }
}
