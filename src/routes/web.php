<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Redaelfillali\LaravelBackup\Controllers\BackupController;
use Redaelfillali\LaravelBackup\Helpers\BackupManager;

Route::post('/backup/run', [BackupController::class, 'run']);

Route::post('/backup/database', static function (): JsonResponse {
    try {
        BackupManager::backup('database', config('backup.path'));

        return response()->json(['message' => 'backup completed']);
    } catch (\Throwable $e) {
        return response()->json(['message' => 'Backup failed: ' . $e->getMessage()], 500);
    }
});

Route::post('/backup/files', static function (): JsonResponse {
    try {
        BackupManager::backup('files', config('backup.path'));

        return response()->json(['message' => 'backup completed']);
    } catch (\Throwable $e) {
        return response()->json(['message' => 'Backup failed: ' . $e->getMessage()], 500);
    }
});
