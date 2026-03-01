<?php

namespace Redaelfillali\LaravelBackup\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Mockery;
use PDO;
use PDOStatement;
use Redaelfillali\LaravelBackup\Tests\TestCase;

class BackupCommandTest extends TestCase
{
    public function test_backup_command_runs_successfully_for_database_type(): void
    {
        $this->mockDatabaseConnection();

        $this->artisan('backup:run', ['type' => 'database', '--path' => $this->backupDir])
            ->assertExitCode(0)
            ->expectsOutputToContain('Backup database completed');
    }

    public function test_backup_command_runs_successfully_for_files_type(): void
    {
        $this->artisan('backup:run', ['type' => 'files', '--path' => $this->backupDir])
            ->assertExitCode(0)
            ->expectsOutputToContain('Backup files completed');
    }

    public function test_backup_command_runs_successfully_for_full_type(): void
    {
        $this->mockDatabaseConnection();

        $this->artisan('backup:run', ['type' => 'full', '--path' => $this->backupDir])
            ->assertExitCode(0)
            ->expectsOutputToContain('Backup full completed');
    }

    public function test_backup_command_uses_default_type_full(): void
    {
        $this->mockDatabaseConnection();

        $this->artisan('backup:run', ['--path' => $this->backupDir])
            ->assertExitCode(0)
            ->expectsOutputToContain('Backup full completed');
    }

    public function test_backup_command_uses_configured_path_when_no_path_given(): void
    {
        $this->mockDatabaseConnection();

        config(['backup.path' => $this->backupDir]);

        $this->artisan('backup:run', ['type' => 'database'])
            ->assertExitCode(0);

        $sqlFiles = glob($this->backupDir . '/database-*.sql');
        $this->assertNotEmpty($sqlFiles);
    }

    public function test_backup_command_returns_failure_on_exception(): void
    {
        DB::shouldReceive('connection')->andThrow(new \RuntimeException('DB connection failed'));

        $this->artisan('backup:run', ['type' => 'database', '--path' => $this->backupDir])
            ->assertExitCode(1)
            ->expectsOutputToContain('Backup failed');
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function mockDatabaseConnection(): void
    {
        $tablesStmt = Mockery::mock(PDOStatement::class);
        $tablesStmt->shouldReceive('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn([]);

        $mockPdo = Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('query')->with('SHOW TABLES')->andReturn($tablesStmt);

        $mockConnection = Mockery::mock(\Illuminate\Database\ConnectionInterface::class);
        $mockConnection->shouldReceive('getPdo')->andReturn($mockPdo);

        DB::shouldReceive('connection')->andReturn($mockConnection);
    }
}
