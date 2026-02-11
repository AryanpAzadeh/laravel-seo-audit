<?php

use AryaAzadeh\LaravelSeoAudit\Analysis\HtmlAnalyzer;
use AryaAzadeh\LaravelSeoAudit\AuditRunner;
use AryaAzadeh\LaravelSeoAudit\Contracts\CrawlerInterface;
use AryaAzadeh\LaravelSeoAudit\Data\CrawlTarget;
use AryaAzadeh\LaravelSeoAudit\Enums\Severity;
use AryaAzadeh\LaravelSeoAudit\RuleEngine;
use AryaAzadeh\LaravelSeoAudit\Rules\TitleExistsRule;
use AryaAzadeh\LaravelSeoAudit\Support\SeoScoreCalculator;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

it('persists audit runs pages and issues', function (): void {
    Schema::dropIfExists('seo_audit_issues');
    Schema::dropIfExists('seo_audit_pages');
    Schema::dropIfExists('seo_audit_runs');

    Schema::create('seo_audit_runs', function (Blueprint $table): void {
        $table->id();
        $table->string('status');
        $table->unsignedSmallInteger('score')->default(0);
        $table->json('totals')->nullable();
        $table->timestamp('started_at')->nullable();
        $table->timestamp('finished_at')->nullable();
        $table->timestamps();
    });

    Schema::create('seo_audit_pages', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('run_id');
        $table->string('url');
        $table->string('source')->default('route');
        $table->unsignedSmallInteger('status_code')->default(200);
        $table->string('title')->nullable();
        $table->unsignedInteger('word_count')->default(0);
        $table->unsignedInteger('issues_count')->default(0);
        $table->timestamps();
    });

    Schema::create('seo_audit_issues', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('run_id');
        $table->unsignedBigInteger('page_id');
        $table->string('rule');
        $table->string('severity', 16);
        $table->text('message');
        $table->json('context')->nullable();
        $table->timestamps();
    });

    $crawler = new class implements CrawlerInterface {
        public function crawl(int $maxPages = 100): array
        {
            return [new CrawlTarget('http://localhost/persist', '/persist', 'route')];
        }
    };

    $runner = new AuditRunner(
        $crawler,
        new HtmlAnalyzer(),
        new RuleEngine([new TitleExistsRule()]),
        new SeoScoreCalculator(),
        app(Kernel::class),
    );

    Route::middleware('web')->get('/persist', fn () => '<html><head></head><body><h1>Persist</h1></body></html>');

    $report = $runner->run(10);

    expect($report->summary->error)->toBeGreaterThanOrEqual(1)
        ->and(DB::table('seo_audit_runs')->count())->toBe(1)
        ->and(DB::table('seo_audit_pages')->count())->toBe(1)
        ->and(DB::table('seo_audit_issues')->count())->toBeGreaterThanOrEqual(1)
        ->and(DB::table('seo_audit_issues')->first()->severity)->toBe(Severity::Error->value);
});
