<?php

namespace AryaAzadeh\LaravelSeoAudit\Support;

use AryaAzadeh\LaravelSeoAudit\Data\SeoPageResult;

class ContentSuggestionBuilder
{
    public function suggestTitle(SeoPageResult $page, ?string $focusKeyword = null): string
    {
        $max = max(10, (int) config('seo-audit.content.title.max', 60));
        $siteName = trim((string) config('seo-audit.content.site_name', ''));

        $base = $this->firstNonEmpty([
            $page->h1Text,
            $page->title,
            $this->humanizePathTail($page->url),
            'Page',
        ]);

        if ($focusKeyword !== null && $focusKeyword !== '' && ! $this->containsInsensitive($base, $focusKeyword)) {
            $base = trim($focusKeyword.' - '.$base);
        }

        if ($siteName !== '' && ! $this->containsInsensitive($base, $siteName)) {
            $withSiteName = $base.' - '.$siteName;
            if (mb_strlen($withSiteName) <= $max) {
                $base = $withSiteName;
            }
        }

        return $this->truncateToBoundary($base, $max);
    }

    public function suggestMetaDescription(SeoPageResult $page, ?string $focusKeyword = null): string
    {
        $min = max(40, (int) config('seo-audit.content.meta_description.min', 120));
        $max = max($min, (int) config('seo-audit.content.meta_description.max', 160));

        $base = $this->firstNonEmpty([
            $page->firstParagraph,
            $page->metaDescription,
            $page->h1Text,
            $page->title,
            'Learn more about this page and what it offers.',
        ]);

        if ($focusKeyword !== null && $focusKeyword !== '' && ! $this->containsInsensitive($base, $focusKeyword)) {
            $base = trim($focusKeyword.'. '.$base);
        }

        $base = $this->truncateToBoundary($base, $max);

        if (mb_strlen($base) < $min) {
            $fallback = $this->firstNonEmpty([$page->title, $page->h1Text, 'This page']);
            $expanded = trim($base.' '.$fallback);
            $base = $this->truncateToBoundary($expanded, $max);
        }

        return $base;
    }

    private function humanizePathTail(string $url): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn (string $segment): bool => $segment !== ''));
        if ($segments === []) {
            return 'Home';
        }

        $tail = (string) end($segments);
        $tail = str_replace(['-', '_'], ' ', $tail);

        return trim($tail) !== '' ? ucfirst(trim($tail)) : 'Page';
    }

    /** @param array<int, string|null> $values */
    private function firstNonEmpty(array $values): string
    {
        foreach ($values as $value) {
            $candidate = trim((string) $value);
            if ($candidate !== '') {
                return preg_replace('/\s+/u', ' ', $candidate) ?? $candidate;
            }
        }

        return '';
    }

    private function containsInsensitive(string $haystack, string $needle): bool
    {
        return mb_stripos($haystack, $needle) !== false;
    }

    private function truncateToBoundary(string $text, int $max): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (mb_strlen($clean) <= $max) {
            return $clean;
        }

        $truncated = trim(mb_substr($clean, 0, $max));
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > (int) ($max * 0.6)) {
            $truncated = trim(mb_substr($truncated, 0, $lastSpace));
        }

        return rtrim($truncated, " \t\n\r\0\x0B,.;:-");
    }
}
