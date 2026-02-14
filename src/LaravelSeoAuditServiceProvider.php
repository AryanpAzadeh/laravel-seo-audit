<?php

namespace AryaAzadeh\LaravelSeoAudit;

use AryaAzadeh\LaravelSeoAudit\Commands\LaravelSeoAuditCommand;
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
            ->hasViews()
            ->hasMigration('create_laravel_seo_audit_table')
            ->hasCommand(LaravelSeoAuditCommand::class);
    }
}
