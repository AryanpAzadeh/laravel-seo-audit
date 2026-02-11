# Laravel SEO Audit — Development Roadmap (MVP Decision-Complete)

## Package Identity

- Package: `aryaazadeh/laravel-seo-audit`
- Base Scaffold: `spatie/package-skeleton-laravel`
- Primary CLI: `php artisan seo:audit`
- Backward compatibility alias: `php artisan laravel-seo-audit`

## Product Vision

Build a developer-first SEO engineering toolkit for Laravel with deterministic audits, structured outputs, secure dashboard visibility, and CI-ready behavior.

## MVP Scope (v0.x)

Included in MVP:

- Core audit pipeline (crawler + analyzer + rules + rule engine)
- Reporting outputs (table/json/html)
- Database persistence for runs/pages/issues
- Protected Blade dashboard
- CI failure gating
- AI contract only (no provider implementation)

Not in MVP:

- Full enterprise feature set
- Advanced AI generation workflows
- Browser-level crawling

## System Layers

| Layer | Responsibility |
| --- | --- |
| Crawler | Discover public web pages from Laravel routes, fallback to HTTP |
| Analyzer | Parse HTML and extract basic SEO signals |
| Rule Engine | Evaluate deterministic SEO rules |
| Reporting | Emit table/json/html outputs |
| Persistence | Store run/page/issue records in DB |
| UI Layer | Auth-gated Blade dashboard |
| CI Layer | Exit-code driven pipeline gating |
| AI Boundary | Contract + feature flag only |

## Architecture Contracts

- `CrawlerInterface`: `crawl(int $maxPages): array<CrawlTarget>`
- `AnalyzerInterface`: `analyze(CrawlTarget $target, ?string $html, int $statusCode): SeoPageResult`
- `RuleInterface`: `evaluate(SeoPageResult $page): array<SeoIssue>`
- `ReporterInterface`: `render(SeoReport $report): string`
- `LlmProviderInterface`: `suggestMeta(string $url, string $content): array{title:?string,description:?string}`

## Public Data Contracts

- `SeoIssue`
- `SeoPageResult`
- `SeoRunSummary`
- `SeoReport`
- Severity enum/constant values: `info`, `warning`, `error`, `critical`
- JSON output versioning key: `report_version`

## CLI Contract

```bash
php artisan seo:audit \
  --format=table|json|html \
  --fail-on=error|critical \
  --output=path \
  --max-pages=int
```

Exit code policy:

- `0`: pass
- `2`: `--fail-on=error` and at least one error found
- `3`: any critical found

## Crawl Policy

Default crawl mode: `Route + HTTP Fallback`

Route selection defaults:

- only `GET` routes
- includes `web` middleware
- excludes auth-protected routes (`auth*`, `verified`, `password.confirm`, `signed`, `can:*`)

Fallback behavior:

- if no crawlable route is found and fallback is enabled, crawl `APP_URL`

## Reporting Storage Model

DB is the source of truth for dashboard and history.

### `seo_audit_runs`

- `id`
- `status`
- `score`
- `totals` (json)
- `started_at`, `finished_at`
- timestamps

### `seo_audit_pages`

- `id`
- `run_id`
- `url`
- `source`
- `status_code`
- `title`
- `word_count`
- `issues_count`
- timestamps

### `seo_audit_issues`

- `id`
- `run_id`
- `page_id`
- `rule`
- `severity`
- `message`
- `context` (json)
- timestamps

## Dashboard Security

Route:

```text
/seo-audit/dashboard
```

Default protections:

- middleware: `web`, `auth`
- gate ability: `viewSeoAudit`

## AI v1 Boundary

MVP includes only:

- provider interface (`LlmProviderInterface`)
- config flags (`ai.enabled`, `ai.provider`, `ai.timeout`)
- safe default provider binding (null provider)

MVP excludes:

- real external provider implementations
- token metering and budget enforcement

## Config Keys (MVP)

```php
crawl.mode
crawl.route_filters
crawl.http_fallback
report.storage
report.retention_days
report.schema_version
ci.fail_on
dashboard.enabled
dashboard.middleware
dashboard.ability
ai.enabled
ai.provider
ai.timeout
```

## Delivery Phases

### Phase 1 — Core Infrastructure

- service provider wiring
- contracts and DTOs
- severity system
- base command

### Phase 2 — Analysis Pipeline

- route crawler
- html analyzer
- initial rules (`title`, `meta description`, `single h1`)
- rule engine orchestration

### Phase 3 — Reporting and Persistence

- json/html/table reporting
- score calculation
- run/page/issue persistence

### Phase 4 — Dashboard + CI

- auth-gated dashboard
- CI exit code contract
- docs alignment

### Phase 5+ — Enterprise and AI Expansion

- advanced cross-page validations
- content intelligence layer
- optional AI providers

## Test Strategy and Acceptance

Required scenarios:

1. Rule engine aggregates issue severities correctly.
2. Crawler includes public web GET routes and excludes auth routes.
3. HTTP fallback activates when no crawlable routes are found.
4. Command emits valid JSON and returns exit code `2` on error threshold.
5. Dashboard blocks guests and allows authorized users.
6. Audit persistence creates run/page/issue records.
7. AI interface resolves to a safe provider when AI is disabled.

## Non-Functional Requirements

- predictable runtime for typical Laravel apps
- safe behavior when external AI is disabled or unavailable
- extensible rule architecture
- maintainable test coverage around core pipeline

## Versioning Strategy

- `0.x`: MVP hardening and contract stabilization
- `1.0`: stable core CLI + dashboard + CI contracts
- `2.x`: enterprise and AI maturity

## Definition of Done (MVP)

- full-app audit can run through `seo:audit`
- structured report output is available (`table`, `json`, `html`)
- score and severity totals are deterministic
- dashboard shows latest run history and is protected
- CI can fail on configured threshold
- AI remains optional and safe by default
