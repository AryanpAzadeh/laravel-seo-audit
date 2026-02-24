# Laravel SEO Audit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aryaazadeh/laravel-seo-audit.svg?style=flat-square)](https://packagist.org/packages/aryaazadeh/laravel-seo-audit)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aryaazadeh/laravel-seo-audit/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/aryaazadeh/laravel-seo-audit/actions?query=workflow%3Arun-tests+branch%3Amain)

Developer-first SEO auditing for Laravel apps with deterministic checks, report persistence, and a protected dashboard.

## Installation

```bash
composer require aryaazadeh/laravel-seo-audit
```

Publish migrations and config:

```bash
php artisan vendor:publish --tag="seo-audit-migrations"
php artisan vendor:publish --tag="seo-audit-config"
php artisan migrate
```

## CLI Usage

Primary command:

```bash
php artisan seo:audit
```

Legacy alias (kept for backwards compatibility):

```bash
php artisan laravel-seo-audit
```

Useful options:

```bash
php artisan seo:audit --format=json --fail-on=error --output=storage/app/seo-report.json --max-pages=100
```

- `--format=table|json|html`
- `--fail-on=error|critical`
- `--output=path`
- `--max-pages=int`

Exit codes:

- `0`: pass
- `2`: error threshold reached
- `3`: critical threshold reached

## Dashboard

Route: `/seo-audit/dashboard`

Default protection:

- middleware: `web`, `auth`
- ability: `viewSeoAudit`

You can configure both in `config/seo-audit.php`.

Dashboard capabilities:

- Select any recent run from the run switcher.
- Track score trend across recent runs.
- Inspect high-risk pages and rule-level issue share.
- Filter issues by `severity`, `rule`, and text query (`q`) with pagination.

## Route Crawl Controls

For multilingual apps and dynamic endpoints, these options help keep reports clean:

- `crawl.exclude_parameterized_routes` (default: `true`) skips routes like `/products/{slug}`.
- `crawl.deduplicate_localized_routes` (default: `true`) deduplicates locale-prefixed duplicates (for example `/fa/about-us` and `/about-us`).
- Internal route redirects are followed before SEO rules are evaluated (for example `/products` -> `/fa/products`).
- `crawl.route_http_fallback_on_error` (default: `true`) retries route-based targets via real HTTP when internal CLI kernel matching returns `4xx/5xx` unexpectedly.
- `crawl.link_discovery.enabled` (default: `false`) discovers extra internal links from crawled HTML pages (useful for dynamic slug pages and old content archives).
- `crawl.link_discovery.seed_paths` controls where discovery starts (default: `['/']`).
- `crawl.link_discovery.max_pages` limits discovery fetches (default: `120`).

## AI Layer (v1 Boundary)

The package exposes an AI provider contract but ships with a safe null provider by default.

- interface: `AryaAzadeh\LaravelSeoAudit\Contracts\LlmProviderInterface`
- default binding: `NullLlmProvider`
- feature flag: `seo-audit.ai.enabled`

## Testing

```bash
composer test
```
