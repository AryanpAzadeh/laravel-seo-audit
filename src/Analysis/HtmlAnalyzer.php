<?php

namespace AryaAzadeh\LaravelSeoAudit\Analysis;

use AryaAzadeh\LaravelSeoAudit\Contracts\AnalyzerInterface;
use AryaAzadeh\LaravelSeoAudit\Data\CrawlTarget;
use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;
use DOMDocument;
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
        $text = trim((string) preg_replace('/\s+/', ' ', (string) $dom->textContent));

        return new SeoPageResult(
            url: $target->url,
            statusCode: $statusCode,
            source: $target->source,
            title: $title !== '' ? $title : null,
            metaDescription: $metaDescription !== '' ? $metaDescription : null,
            h1Count: $h1Count,
            wordCount: $text === '' ? 0 : count(explode(' ', $text)),
        );
    }
}
