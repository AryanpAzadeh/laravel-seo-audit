<?php

return [
    'crawl' => [
        'mode' => 'route_http_fallback',
        'route_filters' => [
            'middleware' => ['web'],
            'exclude_middleware' => ['auth', 'verified', 'password.confirm', 'signed'],
        ],
        'http_fallback' => true,
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
