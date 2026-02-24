<?php

namespace AryaAzadeh\LaravelSeoAudit;

use AryaAzadeh\LaravelSeoAudit\Analysis\HtmlAnalyzer;
use AryaAzadeh\LaravelSeoAudit\Contracts\AnalyzerInterface;
use AryaAzadeh\LaravelSeoAudit\Contracts\CrawlerInterface;
use AryaAzadeh\LaravelSeoAudit\Contracts\LlmProviderInterface;
use AryaAzadeh\LaravelSeoAudit\Crawling\RouteCrawler;
use AryaAzadeh\LaravelSeoAudit\Commands\LaravelSeoAuditCommand;
use AryaAzadeh\LaravelSeoAudit\Rules\MetaDescriptionRule;
use AryaAzadeh\LaravelSeoAudit\Rules\SingleH1Rule;
use AryaAzadeh\LaravelSeoAudit\Rules\TitleExistsRule;
use AryaAzadeh\LaravelSeoAudit\Support\NullLlmProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelSeoAuditServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-seo-audit')
            ->hasConfigFile()
            ->hasViews('laravel-seo-audit')
            ->hasRoute('seo-audit')
            ->hasMigration('create_laravel_seo_audit_table')
            ->hasCommand(LaravelSeoAuditCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->bind(CrawlerInterface::class, RouteCrawler::class);
        $this->app->bind(AnalyzerInterface::class, HtmlAnalyzer::class);

        $this->app->singleton(RuleEngine::class, static function (): RuleEngine {
            return new RuleEngine([
                new TitleExistsRule,
                new MetaDescriptionRule,
                new SingleH1Rule,
            ]);
        });

        $this->app->singleton(NullLlmProvider::class);
        $this->app->bind(LlmProviderInterface::class, function () {
            $provider = config('seo-audit.ai.provider');

            if (is_string($provider) && $provider !== '' && is_a($provider, LlmProviderInterface::class, true)) {
                return app($provider);
            }

            return app(NullLlmProvider::class);
        });
    }

    public function packageBooted()
    {
        $ability = config('seo-audit.dashboard.ability');

        if (is_string($ability) && $ability !== '' && ! Gate::has($ability)) {
            Gate::define($ability, static fn (?Authenticatable $user): bool => $user !== null);
        }
    }
}
