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
        'sitemap_discovery' => [
            'enabled' => false,
            'seed_paths' => ['/sitemap.xml', '/sitemap_index.xml'],
            'max_sitemaps' => 20,
            'max_urls' => 1000,
            'include_query' => false,
            'exclude_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'pdf', 'zip', 'mp4', 'mp3', 'css', 'js', 'json', 'xml'],
        ],
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

    'content' => [
        'enabled' => true,
        'site_name' => null,
        'title' => [
            'min' => 30,
            'max' => 60,
        ],
        'meta_description' => [
            'min' => 120,
            'max' => 160,
        ],
        'min_word_count' => 300,
        'min_words_for_subheadings' => 350,
        'min_words_for_internal_links' => 250,
        'min_internal_links' => 2,
        // Wildcard patterns or regex entries (prefix: regex:).
        // Example: '/fa/products/*' => 'محصولات'
        'focus_keywords' => [],
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
