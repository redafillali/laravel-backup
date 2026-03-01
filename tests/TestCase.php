<?php

namespace Redaelfillali\LaravelBackup\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Redaelfillali\LaravelBackup\BackupServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected string $backupDir;

    protected function getPackageProviders($app): array
    {
        return [BackupServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $this->backupDir = sys_get_temp_dir() . '/laravel-backup-test-' . uniqid();

        $app['config']->set('backup.path', $this->backupDir);
        $app['config']->set('backup.retention_period', 7);
        $app['config']->set('backup.timeout', 30);
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'database' => 'test_db',
            'username' => 'root',
            'password' => '',
        ]);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->backupDir)) {
            \Illuminate\Support\Facades\File::deleteDirectory($this->backupDir);
        }
        parent::tearDown();
    }
}
