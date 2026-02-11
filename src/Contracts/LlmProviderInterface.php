<?php

namespace AryaAzadeh\LaravelSeoAudit\Contracts;

interface LlmProviderInterface
{
    /** @return array{title: string|null, description: string|null} */
    public function suggestMeta(string $url, string $content): array;
}
