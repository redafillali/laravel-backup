<?php

namespace Redaelfillali\LaravelBackup\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BackupManager
{
    /**
     * Perform the backup based on type: 'files', 'database', or 'full'.
     */
    public static function backup(string $type, string $path): bool
    {
        set_time_limit((int) config('backup.timeout', 300));

        self::ensureDirectoryExists($path);

        return match ($type) {
            'files'    => self::backupFiles($path),
            'database' => self::backupDatabase($path),
            default    => self::backupFull($path),
        };
    }

    /**
     * Ensure the backup directory exists, creating it if necessary.
     */
    protected static function ensureDirectoryExists(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Run both a files backup and a database backup.
     */
    protected static function backupFull(string $path): bool
    {
        self::backupFiles($path);
        self::backupDatabase($path);

        return true;
    }

    /**
     * Backup application files into a ZIP archive.
     * Excludes vendor/, node_modules/ and the backup destination directory itself.
     */
    protected static function backupFiles(string $path): bool
    {
        $zipPath         = $path . '/files-' . date('Y-m-d-H-i-s') . '.zip';
        $sourceDirectory = base_path();

        $excludePaths = array_filter([
            $sourceDirectory . DIRECTORY_SEPARATOR . 'vendor',
            $sourceDirectory . DIRECTORY_SEPARATOR . 'node_modules',
            realpath($path) ?: $path,
        ]);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create ZIP archive: ' . $zipPath);
        }

        self::addFilesToZip($zip, $sourceDirectory, $sourceDirectory, $excludePaths);
        $zip->close();

        return true;
    }

    /**
     * Recursively add files from a directory to the ZIP archive, honouring exclusions.
     *
     * @param string[] $excludePaths Absolute paths that should be skipped.
     */
    protected static function addFilesToZip(
        ZipArchive $zip,
        string $directory,
        string $baseDirectory,
        array $excludePaths = []
    ): void {
        foreach (File::allFiles($directory) as $file) {
            $realPath = $file->getRealPath();

            foreach ($excludePaths as $excludePath) {
                if (str_starts_with($realPath, $excludePath . DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            $relativePath = substr($realPath, strlen($baseDirectory) + 1);
            $zip->addFile($realPath, $relativePath);
        }
    }

    /**
     * Backup the database into a .sql file using PDO (MySQL only).
     */
    protected static function backupDatabase(string $path): bool
    {
        $connectionName = config('database.default', 'mysql');
        $database       = config("database.connections.{$connectionName}.database");
        $backupFile     = $path . '/database-' . date('Y-m-d-H-i-s') . '.sql';

        // Fix: use the resolved connection name, not the hard-coded default.
        $pdo    = DB::connection($connectionName)->getPdo();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $sqlDump = "-- SQL Dump for Database: {$database}\n\n";

        foreach ($tables as $table) {
            $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);

            $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sqlDump .= $createRow['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values  = array_map(
                    static fn (mixed $value): string => $value === null ? 'NULL' : $pdo->quote((string) $value),
                    array_values($row)
                );

                $sqlDump .= 'INSERT INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) VALUES ('
                    . implode(', ', $values) . ");\n";
            }

            $sqlDump .= "\n";
        }

        File::put($backupFile, $sqlDump);

        self::applyRetentionPolicy($path);

        return true;
    }

    /**
     * Delete backup files older than the configured retention period.
     */
    protected static function applyRetentionPolicy(string $path): void
    {
        $maxAge = (int) config('backup.retention_period', 7) * 24 * 60 * 60;
        $now    = time();

        foreach (File::files($path) as $file) {
            if (($now - $file->getMTime()) > $maxAge) {
                File::delete($file);
            }
        }
    }
}
