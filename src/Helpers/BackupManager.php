<?php

namespace Redaelfillali\LaravelBackup\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupManager
{
    public static function backup(string $type, string $path)
    {
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

    protected static function backupFiles(string $path)
    {
        $zipPath = $path . '/files-backup.zip';
        $files = base_path(); // Chemin vers les fichiers du projet
        // Ajoutez la logique pour archiver les fichiers
        File::copyDirectory($files, $zipPath);
    }

    protected static function backupDatabase(string $path)
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $backupFile = $path . '/database.sql';

        // Export de la BDD sans utiliser proc_open
        $pdo = DB::connection()->getPdo();
        $query = "SHOW TABLES";
        $tables = $pdo->query($query)->fetchAll();

        File::put($backupFile, '--- SQL DUMP ---');
    }
}
