<?php

namespace Redaelfillali\LaravelBackup\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class BackupManager
{
  /**
   * Perform the backup based on type: 'files', 'database', or 'full'.
   */
  public static function backup(string $type, string $path): bool
  {
    // Get the timeout value from the config
    $timeout = config('backup.timeout', 300);

    // Set max execution time to the configured value
    set_time_limit($timeout);

    // Ensure the backup directory exists
    self::ensureDirectoryExists($path);

    switch ($type) {
      case 'files':
        return self::backupFiles($path);
      case 'database':
        return self::backupDatabase($path);
      case 'full':
      default:
        self::backupFiles($path);
        self::backupDatabase($path);
        return true;
    }
  }

  /**
   * Ensure the backup directory exists, if not create it.
   */
  protected static function ensureDirectoryExists(string $path): void
  {
    if (!File::exists($path)) {
      File::makeDirectory($path, 0755, true);
    }
  }

  /**
   * Backup the files of the application into a zip archive.
   */
  protected static function backupFiles(string $path): bool
  {
    $zipPath = $path . '/files-' . date('Y-m-d-H-i-s') . '.zip';
    $sourceDirectory = base_path();

    $zip = new ZipArchive;

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
      self::addFilesToZip($zip, $sourceDirectory, $sourceDirectory);
      $zip->close();
    } else {
      throw new \Exception('Could not create ZIP archive.');
    }

    return true;
  }

  /**
   * Add files from a directory to the zip archive.
   * Uses File::allFiles() which already recurses into subdirectories.
   */
  protected static function addFilesToZip(ZipArchive $zip, string $directory, string $baseDirectory): void
  {
    foreach (File::allFiles($directory) as $file) {
      $relativePath = substr($file->getRealPath(), strlen($baseDirectory) + 1);
      $zip->addFile($file->getRealPath(), $relativePath);
    }
  }

  /**
   * Backup the database into a .sql file.
   */
  protected static function backupDatabase(string $path): bool
  {
    $connectionName = config('database.default', 'mysql');
    $database = config("database.connections.{$connectionName}.database");
    $backupFile = $path . '/database-' . date('Y-m-d-H-i-s') . '.sql';

    $pdo = DB::connection()->getPdo();
    $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

    $sqlDump = "-- SQL Dump for Database: $database\n\n";

    foreach ($tables as $table) {
      // Get table structure
      $createTableQuery = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
      $sqlDump .= "\n\n" . $createTableQuery['Create Table'] . ";\n\n";

      // Get table data
      $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
      foreach ($rows as $row) {
        $columns = array_keys($row);
        $values = array_map(function ($value) use ($pdo) {
          return is_null($value) ? 'NULL' : $pdo->quote((string) $value);
        }, array_values($row));

        $sqlDump .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
      }
    }

    File::put($backupFile, $sqlDump);

    // Clear backups older than the retention period
    $retentionPeriod = config('backup.retention_period', 7) * 24 * 60 * 60;
    $now = time();

    foreach (File::files($path) as $file) {
      if ($now - $file->getMTime() >= $retentionPeriod) {
        File::delete($file);
      }
    }

    return true;
  }
}
