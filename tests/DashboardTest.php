<?php

use AryaAzadeh\LaravelSeoAudit\Models\AuditPage;
use AryaAzadeh\LaravelSeoAudit\Models\AuditRun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

it('requires auth for dashboard and allows authorized users', function (): void {
    config()->set('seo-audit.dashboard.middleware', ['web', 'auth']);

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

    Route::get('/login', fn () => 'login')->name('login');

    $run = AuditRun::query()->create([
        'status' => 'completed',
        'score' => 80,
        'totals' => ['pages' => 1, 'issues' => 1],
    ]);

    AuditPage::query()->create([
        'run_id' => $run->id,
        'url' => 'http://localhost/page',
        'source' => 'route',
        'status_code' => 200,
        'title' => 'Page',
        'word_count' => 10,
        'issues_count' => 1,
    ]);

    $this->get('/seo-audit/dashboard')->assertRedirect('/login');

    $user = new class extends User {};
    $this->be($user);

    $this->get('/seo-audit/dashboard')
        ->assertOk()
        ->assertSee('SEO Audit Dashboard');
});
