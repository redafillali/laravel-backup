<?php

namespace Redaelfillali\LaravelBackup\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupManager
{
  /**
   * Perform the backup based on type: 'files', 'database', or 'full'.
   */
  public static function backup(string $type, string $path)
  {
    // Get the timeout value from the config
    $timeout = config('laravel-backup.timeout', 300); // Default to 300 seconds if not defined

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
  protected static function ensureDirectoryExists(string $path)
  {
    // Check if the directory exists, and create it if it does not
    if (!File::exists($path)) {
      File::makeDirectory($path, 0755, true);
    }
  }

  /**
   * Backup the files of the application into a zip archive.
   */
  protected static function backupFiles(string $path)
  {
    $zipPath = $path . '/files-' . date('H-i-s') . '.zip';
    $files = base_path(); // Path to the project files

    // Create a new ZipArchive instance
    $zip = new ZipArchive;

    // Open the archive for writing
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
      // Recursively add files and directories to the zip
      self::addFilesToZip($zip, $files, base_path());
      $zip->close();
    } else {
      throw new \Exception('Could not create ZIP archive.');
    }

    return true;
  }

  /**
   * Recursively add files to the zip archive.
   */
  protected static function addFilesToZip(ZipArchive $zip, $directory, $baseDirectory)
  {
    // Iterate over all files and directories
    $files = File::allFiles($directory);
    foreach ($files as $file) {
      // Get relative path to avoid absolute path storage in the zip
      $relativePath = substr($file->getRealPath(), strlen($baseDirectory) + 1);
      $zip->addFile($file->getRealPath(), $relativePath);
    }

    // Iterate over all subdirectories and add their files
    $directories = File::directories($directory);
    foreach ($directories as $dir) {
      self::addFilesToZip($zip, $dir, $baseDirectory);
    }
  }

  /**
   * Backup the database into a .sql file.
   */
  protected static function backupDatabase(string $path)
  {
    $database = config('database.connections.mysql.database');
    $username = config('database.connections.mysql.username');
    $password = config('database.connections.mysql.password');
    $host = config('database.connections.mysql.host');
    $backupFile = $path . '/database-' . config('backup_name', date('Y-m-d-H-i-s')) . '.sql';

    // Get the list of all tables from the database
    $pdo = DB::connection()->getPdo();
    $query = "SHOW TABLES";
    $tables = $pdo->query($query)->fetchAll(\PDO::FETCH_COLUMN);

    // Initialize the SQL dump content
    $sqlDump = "-- SQL Dump for Database: $database\n\n";

    foreach ($tables as $table) {
      // Get table structure
      $createTableQuery = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
      $sqlDump .= "\n\n" . $createTableQuery['Create Table'] . ";\n\n";

      // Get table data
      $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
      foreach ($rows as $row) {
        $columns = array_keys($row);
        $values = array_map(function ($value) {
          return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
        }, array_values($row));

        $sqlDump .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
      }
    }

    // Save the SQL dump to a file
    File::put($backupFile, $sqlDump);

    return true;
  }
}
