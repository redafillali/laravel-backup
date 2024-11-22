<?php

namespace Redaelfillali\LaravelBackup\Commands;

use Illuminate\Console\Command;
use Redaelfillali\LaravelBackup\Helpers\BackupManager;

class BackupCommand extends Command
{
    protected $signature = 'backup:run {type=full} {--path=}';

    protected $description = 'Run a backup';

    public function handle()
    {
        $type = $this->argument('type');
        $path = $this->option('path') ?? config('backup.path');

        BackupManager::backup($type, $path);

        $this->info("Backup {$type} completed at {$path}");
    }
}
