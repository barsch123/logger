<?php

namespace Gottvergessen\Activity;


use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Gottvergessen\Activity\Commands\ActivityInstallCommand;

class ActivityServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('activity')
            ->hasConfigFile()
            ->hasMigration('create_logger_table')
            ->hasCommand(ActivityInstallCommand::class);
    }

   
}
