<?php

use Illuminate\Support\Facades\Route;
use Redaelfillali\LaravelBackup\Controllers\BackupController;
use Redaelfillali\LaravelBackup\Helpers\BackupManager;

Route::post('/backup/run', [BackupController::class, 'run']);

Route::post('/backup/database', function () {
  $type = 'database';
  $path = config('backup.path');
  BackupManager::backup($type, $path);
  return "backup completed";
});

Route::post('/backup/files', function () {
  $type = 'files';
  $path = config('backup.path');
  BackupManager::backup($type, $path);
  return "backup completed";
});
