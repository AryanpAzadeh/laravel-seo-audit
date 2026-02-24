<?php

return [
    'crawl' => [
        'mode' => 'route_http_fallback',
        'route_filters' => [
            'middleware' => ['web'],
            'exclude_middleware' => ['auth', 'verified', 'password.confirm', 'signed'],
        ],
        'exclude_parameterized_routes' => true,
        'deduplicate_localized_routes' => true,
        // If internal kernel route matching fails in CLI (common with localized route registrars),
        // retry by fetching the real URL over HTTP and use that response for analysis.
        'route_http_fallback_on_error' => true,
        'http_fallback' => true,
        'link_discovery' => [
            'enabled' => false,
            'seed_from_route_targets' => true,
            'seed_paths' => ['/'],
            'max_pages' => 120,
            'include_query' => false,
            'exclude_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'pdf', 'zip', 'mp4', 'mp3', 'css', 'js', 'json', 'xml'],
        ],
    ],

    'report' => [
        'storage' => 'database',
        'retention_days' => 30,
        'schema_version' => '1.0.0',
    ],

    'ci' => [
        'fail_on' => 'error',
    ],

    'dashboard' => [
        'enabled' => true,
        'middleware' => ['web', 'auth'],
        'ability' => 'viewSeoAudit',
    ],

    'ai' => [
        'enabled' => false,
        'provider' => null,
        'timeout' => 15,
    ],

];
