<?php

namespace AryaAzadeh\LaravelSeoAudit;

use AryaAzadeh\LaravelSeoAudit\Analysis\HtmlAnalyzer;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AryaAzadeh\LaravelSeoAudit\Contracts\AnalyzerInterface;
use AryaAzadeh\LaravelSeoAudit\Contracts\CrawlerInterface;
use AryaAzadeh\LaravelSeoAudit\Contracts\LlmProviderInterface;
use AryaAzadeh\LaravelSeoAudit\Commands\LaravelSeoAuditCommand;
use AryaAzadeh\LaravelSeoAudit\Crawling\RouteCrawler;
use AryaAzadeh\LaravelSeoAudit\Rules\MetaDescriptionRule;
use AryaAzadeh\LaravelSeoAudit\Rules\SingleH1Rule;
use AryaAzadeh\LaravelSeoAudit\Rules\TitleExistsRule;
use AryaAzadeh\LaravelSeoAudit\Support\NullLlmProvider;
use AryaAzadeh\LaravelSeoAudit\Support\SeoScoreCalculator;
use Illuminate\Support\Facades\Gate;

class LaravelSeoAuditServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-seo-audit')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_seo_audit_runs_table')
            ->hasMigration('create_seo_audit_pages_table')
            ->hasMigration('create_seo_audit_issues_table')
            ->hasCommand(LaravelSeoAuditCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->bind(CrawlerInterface::class, RouteCrawler::class);
        $this->app->bind(AnalyzerInterface::class, HtmlAnalyzer::class);
        $this->app->bind(LlmProviderInterface::class, NullLlmProvider::class);
        $this->app->singleton(SeoScoreCalculator::class);
        $this->app->singleton(LaravelSeoAudit::class);
        $this->app->singleton(RuleEngine::class, function (): RuleEngine {
            return new RuleEngine([
                new TitleExistsRule(),
                new MetaDescriptionRule(),
                new SingleH1Rule(),
            ]);
        });
    }

    public function packageBooted(): void
    {
        if (! Gate::has((string) config('seo-audit.dashboard.ability', 'viewSeoAudit'))) {
            Gate::define((string) config('seo-audit.dashboard.ability', 'viewSeoAudit'), static fn ($user): bool => $user !== null);
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/seo-audit.php');
    }
}
