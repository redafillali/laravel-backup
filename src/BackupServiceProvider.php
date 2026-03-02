<?php

namespace Redaelfillali\LaravelBackup;

use Illuminate\Support\ServiceProvider;
use Redaelfillali\LaravelBackup\Commands\BackupCommand;

class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/backup.php', 'backup');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([BackupCommand::class]);

            $this->publishes([
                __DIR__ . '/config/backup.php' => config_path('backup.php'),
            ], 'config');
        }
    }
}
