<?php

use AryaAzadeh\LaravelSeoAudit\Contracts\LlmProviderInterface;

it('binds the AI interface to a safe default provider', function (): void {
    config()->set('seo-audit.ai.enabled', false);

    $provider = app(LlmProviderInterface::class);
    $result = $provider->suggestMeta('http://localhost', 'content');

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['title', 'description'])
        ->and($result['title'])->toBeNull()
        ->and($result['description'])->toBeNull();
});
