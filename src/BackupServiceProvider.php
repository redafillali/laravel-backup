<?php

namespace Redaelfillali\LaravelBackup;

use Illuminate\Support\ServiceProvider;

class BackupServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/backup.php', 'backup');
    }

    public function boot()
    {
        // Commande artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Redaelfillali\LaravelBackup\Commands\BackupCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/backup.php' => config_path('backup.php'),
            ], 'config');
        }

        // Chargement des routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
    }
}
