<?php

namespace AryaAzadeh\LaravelSeoAudit\Support;

use AryaAzadeh\LaravelSeoAudit\Contracts\LlmProviderInterface;

class NullLlmProvider implements LlmProviderInterface
{
    public function suggestMeta(string $url, string $content): array
    {
        return ['title' => null, 'description' => null];
    }
}
