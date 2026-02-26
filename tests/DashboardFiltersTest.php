<?php

use AryaAzadeh\LaravelSeoAudit\Models\AuditIssue;
use AryaAzadeh\LaravelSeoAudit\Models\AuditPage;
use AryaAzadeh\LaravelSeoAudit\Models\AuditRun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

it('filters dashboard issues by run severity rule and search query', function (): void {
    config()->set('seo-audit.dashboard.middleware', ['web', 'auth']);

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

    Route::get('/login', fn () => 'login')->name('login');

    $runA = AuditRun::query()->create([
        'status' => 'completed',
        'score' => 82,
        'totals' => ['pages' => 2, 'issues' => 2, 'warning' => 1, 'error' => 1],
    ]);
    $runB = AuditRun::query()->create([
        'status' => 'completed',
        'score' => 90,
        'totals' => ['pages' => 1, 'issues' => 1, 'warning' => 1],
    ]);

    $pageA = AuditPage::query()->create([
        'run_id' => $runA->id,
        'url' => 'http://localhost/a',
        'source' => 'route',
        'status_code' => 200,
        'title' => 'A',
        'word_count' => 10,
        'issues_count' => 1,
    ]);
    $pageB = AuditPage::query()->create([
        'run_id' => $runA->id,
        'url' => 'http://localhost/b',
        'source' => 'route',
        'status_code' => 200,
        'title' => 'B',
        'word_count' => 10,
        'issues_count' => 1,
    ]);
    $pageC = AuditPage::query()->create([
        'run_id' => $runB->id,
        'url' => 'http://localhost/c',
        'source' => 'route',
        'status_code' => 200,
        'title' => 'C',
        'word_count' => 10,
        'issues_count' => 1,
    ]);

    AuditIssue::query()->create([
        'run_id' => $runA->id,
        'page_id' => $pageA->id,
        'rule' => 'meta_description',
        'severity' => 'warning',
        'message' => 'missing meta on A',
        'context' => [],
    ]);
    AuditIssue::query()->create([
        'run_id' => $runA->id,
        'page_id' => $pageB->id,
        'rule' => 'single_h1',
        'severity' => 'error',
        'message' => 'multiple h1 on B',
        'context' => [],
    ]);
    AuditIssue::query()->create([
        'run_id' => $runB->id,
        'page_id' => $pageC->id,
        'rule' => 'meta_description',
        'severity' => 'warning',
        'message' => 'missing meta on C',
        'context' => [],
    ]);

    $user = new class extends User {};
    $this->be($user);

    $this->get('/seo-audit/dashboard?run='.$runA->id.'&severity=warning&rule=meta_description&q=localhost/a')
        ->assertOk()
        ->assertSee('SEO Audit Dashboard')
        ->assertSee('missing meta on A')
        ->assertDontSee('multiple h1 on B')
        ->assertDontSee('missing meta on C');
});

it('filters high-risk pages by search query aliases', function (): void {
    config()->set('seo-audit.dashboard.middleware', ['web', 'auth']);

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

    Route::get('/login', fn () => 'login')->name('login');

    $run = AuditRun::query()->create([
        'status' => 'completed',
        'score' => 91,
        'totals' => ['pages' => 2, 'issues' => 0],
    ]);

    AuditPage::query()->create([
        'run_id' => $run->id,
        'url' => 'http://localhost/needle-page',
        'source' => 'discovered-link',
        'status_code' => 200,
        'title' => 'Needle Title',
        'word_count' => 150,
        'issues_count' => 0,
    ]);

    AuditPage::query()->create([
        'run_id' => $run->id,
        'url' => 'http://localhost/other-page',
        'source' => 'discovered-link',
        'status_code' => 200,
        'title' => 'Other Title',
        'word_count' => 150,
        'issues_count' => 0,
    ]);

    $user = new class extends User {};
    $this->be($user);

    $this->get('/seo-audit/dashboard?run='.$run->id.'&search=needle')
        ->assertOk()
        ->assertSee('http://localhost/needle-page')
        ->assertDontSee('http://localhost/other-page');
});
