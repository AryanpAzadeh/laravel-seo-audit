<?php

namespace AryaAzadeh\LaravelSeoAudit\Data;

final class CrawlTarget
{
    public function __construct(
        public string $url,
        public string $path,
        public string $source,
        public ?string $routeName = null,
    ) {}

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'path' => $this->path,
            'source' => $this->source,
            'route_name' => $this->routeName,
        ];
    }
}
