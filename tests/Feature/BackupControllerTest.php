<?php

namespace Redaelfillali\LaravelBackup\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Mockery;
use PDO;
use PDOStatement;
use Redaelfillali\LaravelBackup\Tests\TestCase;

class BackupControllerTest extends TestCase
{
    public function test_run_endpoint_returns_success_for_database_type(): void
    {
        $this->mockDatabaseConnection();

        $response = $this->postJson('/backup/run', [
            'type' => 'database',
            'path' => $this->backupDir,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Backup completed!']);
    }

    public function test_run_endpoint_returns_success_for_files_type(): void
    {
        $response = $this->postJson('/backup/run', [
            'type' => 'files',
            'path' => $this->backupDir,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Backup completed!']);
    }

    public function test_run_endpoint_returns_success_for_full_type(): void
    {
        $this->mockDatabaseConnection();

        $response = $this->postJson('/backup/run', [
            'type' => 'full',
            'path' => $this->backupDir,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Backup completed!']);
    }

    public function test_run_endpoint_returns_422_for_invalid_type(): void
    {
        $response = $this->postJson('/backup/run', [
            'type' => 'invalid-type',
            'path' => $this->backupDir,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid backup type.']);
    }

    public function test_run_endpoint_returns_500_on_backup_exception(): void
    {
        DB::shouldReceive('connection')->andThrow(new \RuntimeException('DB error'));

        $response = $this->postJson('/backup/run', [
            'type' => 'database',
            'path' => $this->backupDir,
        ]);

        $response->assertStatus(500)
            ->assertJsonFragment(['message' => 'Backup failed: DB error']);
    }

    public function test_database_route_returns_backup_completed(): void
    {
        $this->mockDatabaseConnection();

        $response = $this->post('/backup/database');

        $response->assertStatus(200);
        $this->assertStringContainsString('backup completed', $response->getContent());
    }

    public function test_files_route_returns_backup_completed(): void
    {
        $response = $this->post('/backup/files');

        $response->assertStatus(200);
        $this->assertStringContainsString('backup completed', $response->getContent());
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
