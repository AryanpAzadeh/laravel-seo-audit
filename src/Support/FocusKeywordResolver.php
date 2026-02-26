<?php

namespace AryaAzadeh\LaravelSeoAudit\Support;

use Illuminate\Support\Str;

class FocusKeywordResolver
{
    public function resolveForUrl(string $url): ?string
    {
        $map = (array) config('seo-audit.content.focus_keywords', []);
        if ($map === []) {
            return null;
        }

        $path = '/'.ltrim((string) (parse_url($url, PHP_URL_PATH) ?? '/'), '/');

        foreach ($map as $pattern => $keyword) {
            if (! is_string($pattern) || ! is_string($keyword)) {
                continue;
            }

            $trimmedKeyword = trim($keyword);
            if ($trimmedKeyword === '') {
                continue;
            }

            if ($this->matches($pattern, $path, $url)) {
                return $trimmedKeyword;
            }
        }

        return null;
    }

    private function matches(string $pattern, string $path, string $url): bool
    {
        $normalizedPattern = trim($pattern);
        if ($normalizedPattern === '') {
            return false;
        }

        if (str_starts_with($normalizedPattern, 'regex:')) {
            $regex = substr($normalizedPattern, 6);
            if ($regex === false || $regex === '') {
                return false;
            }

            return @preg_match($regex, $path) === 1 || @preg_match($regex, $url) === 1;
        }

        $patternCandidates = array_values(array_unique([
            $normalizedPattern,
            '/'.ltrim($normalizedPattern, '/'),
            ltrim($normalizedPattern, '/'),
        ]));

        foreach ($patternCandidates as $candidate) {
            if (Str::is($candidate, $path) || Str::is($candidate, ltrim($path, '/')) || Str::is($candidate, $url)) {
                return true;
            }
        }

        return false;
    }
}
