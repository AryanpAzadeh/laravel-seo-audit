<?php

namespace AryaAzadeh\LaravelSeoAudit\Data;

use AryaAzadeh\LaravelSeoAudit\Enums\Severity;

final class SeoIssue
{
    public function __construct(
        public string $rule,
        public string $message,
        public Severity $severity,
        public ?string $url = null,
        public array $context = [],
    ) {}

    public function toArray(): array
    {
        return [
            'rule' => $this->rule,
            'message' => $this->message,
            'severity' => $this->severity->value,
            'url' => $this->url,
            'context' => $this->context,
        ];
    }
}
