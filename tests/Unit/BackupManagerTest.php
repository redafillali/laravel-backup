<?php

namespace Redaelfillali\LaravelBackup\Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use PDO;
use PDOStatement;
use Redaelfillali\LaravelBackup\Helpers\BackupManager;
use Redaelfillali\LaravelBackup\Tests\TestCase;

class BackupManagerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Directory creation
    // -------------------------------------------------------------------------

    public function test_backup_creates_directory_when_it_does_not_exist(): void
    {
        $this->assertFalse(File::exists($this->backupDir));

        $this->mockDatabaseConnection([]);

        BackupManager::backup('database', $this->backupDir);

        $this->assertTrue(File::isDirectory($this->backupDir));
    }

    public function test_backup_does_not_fail_when_directory_already_exists(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);
        $this->assertTrue(File::isDirectory($this->backupDir));

        $this->mockDatabaseConnection([]);

        BackupManager::backup('database', $this->backupDir);

        $this->assertTrue(File::isDirectory($this->backupDir));
    }

    // -------------------------------------------------------------------------
    // Files backup
    // -------------------------------------------------------------------------

    public function test_backup_files_creates_zip_archive(): void
    {
        BackupManager::backup('files', $this->backupDir);

        $zips = glob($this->backupDir . '/files-*.zip');
        $this->assertNotEmpty($zips, 'Expected a zip archive to be created.');
        $this->assertFileExists($zips[0]);
    }

    public function test_backup_files_zip_filename_includes_full_datetime(): void
    {
        BackupManager::backup('files', $this->backupDir);

        $zips = glob($this->backupDir . '/files-*.zip');
        $this->assertNotEmpty($zips);
        // Filename should match files-YYYY-MM-DD-HH-II-SS.zip
        $this->assertMatchesRegularExpression(
            '/files-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.zip$/',
            basename($zips[0])
        );
    }

    public function test_backup_files_zip_is_a_valid_archive(): void
    {
        BackupManager::backup('files', $this->backupDir);

        $zips = glob($this->backupDir . '/files-*.zip');
        $this->assertNotEmpty($zips);

        $zip = new \ZipArchive;
        $result = $zip->open($zips[0]);
        $this->assertTrue($result === true, 'ZIP archive should open successfully.');
        $this->assertGreaterThan(0, $zip->numFiles, 'ZIP archive should contain files.');
        $zip->close();
    }

    public function test_backup_files_does_not_duplicate_entries(): void
    {
        BackupManager::backup('files', $this->backupDir);

        $zips = glob($this->backupDir . '/files-*.zip');
        $this->assertNotEmpty($zips);

        $zip = new \ZipArchive;
        $zip->open($zips[0]);

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertCount(count(array_unique($entries)), $entries, 'ZIP should not contain duplicate entries.');
    }

    // -------------------------------------------------------------------------
    // Database backup
    // -------------------------------------------------------------------------

    public function test_backup_database_creates_sql_file(): void
    {
        $this->mockDatabaseConnection([]);

        BackupManager::backup('database', $this->backupDir);

        $sqlFiles = glob($this->backupDir . '/database-*.sql');
        $this->assertNotEmpty($sqlFiles, 'Expected a SQL dump file to be created.');
        $this->assertFileExists($sqlFiles[0]);
    }

    public function test_backup_database_sql_contains_header(): void
    {
        $this->mockDatabaseConnection([]);

        BackupManager::backup('database', $this->backupDir);

        $sqlFiles = glob($this->backupDir . '/database-*.sql');
        $content = file_get_contents($sqlFiles[0]);
        $this->assertStringContainsString('-- SQL Dump for Database:', $content);
    }

    public function test_backup_database_includes_table_structure_and_data(): void
    {
        $createStatement = 'CREATE TABLE `users` (`id` int(11) NOT NULL, `name` varchar(255) NOT NULL)';

        $this->mockDatabaseConnection(
            tables: ['users'],
            createStatements: ['users' => $createStatement],
            tableData: ['users' => [['id' => 1, 'name' => 'Alice']]]
        );

        BackupManager::backup('database', $this->backupDir);

        $sqlFiles = glob($this->backupDir . '/database-*.sql');
        $content = file_get_contents($sqlFiles[0]);

        $this->assertStringContainsString($createStatement, $content);
        $this->assertStringContainsString("INSERT INTO `users`", $content);
        $this->assertStringContainsString('Alice', $content);
    }

    public function test_backup_database_uses_pdo_quote_for_values(): void
    {
        // Value contains a single quote — PDO::quote() should escape it as '' (not backslash)
        $this->mockDatabaseConnection(
            tables: ['users'],
            createStatements: ['users' => "CREATE TABLE `users` (`name` varchar(255))"],
            tableData: ['users' => [['name' => "O'Brien"]]]
        );

        BackupManager::backup('database', $this->backupDir);

        $sqlFiles = glob($this->backupDir . '/database-*.sql');
        $content = file_get_contents($sqlFiles[0]);

        // PDO::quote() escapes single quotes as '' (SQL standard); addslashes() would produce \'
        $this->assertStringContainsString("O''Brien", $content, "PDO::quote() should double single quotes.");
        $this->assertStringNotContainsString("O\\'Brien", $content, "addslashes() style escaping should not be used.");
    }

    public function test_backup_database_handles_null_values(): void
    {
        $this->mockDatabaseConnection(
            tables: ['users'],
            createStatements: ['users' => "CREATE TABLE `users` (`id` int, `bio` text)"],
            tableData: ['users' => [['id' => 1, 'bio' => null]]]
        );

        BackupManager::backup('database', $this->backupDir);

        $sqlFiles = glob($this->backupDir . '/database-*.sql');
        $content = file_get_contents($sqlFiles[0]);

        $this->assertStringContainsString('NULL', $content);
    }

    public function test_backup_database_uses_configured_connection_name(): void
    {
        // The fix: should use config('database.default') instead of hardcoded 'mysql'
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'my_app_db']);

        $this->mockDatabaseConnection([]);

        BackupManager::backup('database', $this->backupDir);

        $sqlFiles = glob($this->backupDir . '/database-*.sql');
        $content = file_get_contents($sqlFiles[0]);

        $this->assertStringContainsString('my_app_db', $content);
    }

    public function test_backup_database_retention_removes_old_files(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);

        // Create a fake old SQL file with an old timestamp
        $oldFile = $this->backupDir . '/database-old.sql';
        File::put($oldFile, '-- old backup');
        // Set modification time to 10 days ago
        touch($oldFile, time() - (10 * 24 * 60 * 60));

        $this->mockDatabaseConnection([]);

        config(['backup.retention_period' => 7]);

        BackupManager::backup('database', $this->backupDir);

        $this->assertFileDoesNotExist($oldFile, 'Old backup file should have been deleted by retention cleanup.');
    }

    public function test_backup_database_retention_keeps_recent_files(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);

        // Create a recent SQL file (3 days old, within 7-day retention)
        $recentFile = $this->backupDir . '/database-recent.sql';
        File::put($recentFile, '-- recent backup');
        touch($recentFile, time() - (3 * 24 * 60 * 60));

        $this->mockDatabaseConnection([]);

        config(['backup.retention_period' => 7]);

        BackupManager::backup('database', $this->backupDir);

        $this->assertFileExists($recentFile, 'Recent backup file should be kept.');
    }

    // -------------------------------------------------------------------------
    // Full backup
    // -------------------------------------------------------------------------

    public function test_backup_full_creates_both_zip_and_sql_files(): void
    {
        $this->mockDatabaseConnection([]);

        BackupManager::backup('full', $this->backupDir);

        $zips = glob($this->backupDir . '/files-*.zip');
        $sqlFiles = glob($this->backupDir . '/database-*.sql');

        $this->assertNotEmpty($zips, 'Full backup should create a zip archive.');
        $this->assertNotEmpty($sqlFiles, 'Full backup should create a SQL dump.');
    }

    public function test_backup_default_type_is_full(): void
    {
        // The switch defaults to 'full' for unknown types
        $this->mockDatabaseConnection([]);

        $result = BackupManager::backup('unknown-type', $this->backupDir);

        $this->assertTrue($result);

        $zips = glob($this->backupDir . '/files-*.zip');
        $sqlFiles = glob($this->backupDir . '/database-*.sql');

        $this->assertNotEmpty($zips);
        $this->assertNotEmpty($sqlFiles);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Mock DB::connection()->getPdo() to return a fake PDO.
     *
     * @param string[] $tables
     * @param array<string,string> $createStatements
     * @param array<string,array<int,array<string,mixed>>> $tableData
     */
    private function mockDatabaseConnection(
        array $tables = [],
        array $createStatements = [],
        array $tableData = []
    ): void {
        $mockPdo = Mockery::mock(PDO::class);

        // SHOW TABLES
        $tablesStmt = Mockery::mock(PDOStatement::class);
        $tablesStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->andReturn($tables);
        $mockPdo->shouldReceive('query')
            ->with('SHOW TABLES')
            ->andReturn($tablesStmt);

        foreach ($tables as $table) {
            // SHOW CREATE TABLE
            $createStmt = Mockery::mock(PDOStatement::class);
            $createStmt->shouldReceive('fetch')
                ->with(PDO::FETCH_ASSOC)
                ->andReturn(['Create Table' => $createStatements[$table] ?? "CREATE TABLE `$table` ()"]);
            $mockPdo->shouldReceive('query')
                ->with("SHOW CREATE TABLE `$table`")
                ->andReturn($createStmt);

            // SELECT *
            $dataStmt = Mockery::mock(PDOStatement::class);
            $dataStmt->shouldReceive('fetchAll')
                ->with(PDO::FETCH_ASSOC)
                ->andReturn($tableData[$table] ?? []);
            $mockPdo->shouldReceive('query')
                ->with("SELECT * FROM `$table`")
                ->andReturn($dataStmt);
        }

        // PDO::quote() — wrap value in single quotes and escape internal single quotes
        $mockPdo->shouldReceive('quote')
            ->andReturnUsing(fn (mixed $v) => "'" . str_replace("'", "''", (string) $v) . "'");

        $mockConnection = Mockery::mock(\Illuminate\Database\ConnectionInterface::class);
        $mockConnection->shouldReceive('getPdo')->andReturn($mockPdo);

        DB::shouldReceive('connection')->andReturn($mockConnection);
    }
}
