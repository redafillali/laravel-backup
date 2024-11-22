<?php

use Redaelfillali\LaravelBackup\Controllers\BackupController;
use Redaelfillali\LaravelBackup\Helpers\BackupManager;

Route::get('backup/run', [BackupController::class, 'run']);
Route::get('/backup/database', function() {
  $type = 'database';
  $path = config('backup.path');
  BackupManager::backup($type, $path);
  return "backup completed";
});
Route::get('/backup/files', function() {
  $type = 'files';
  $path = config('backup.path');
  BackupManager::backup($type, $path);
  return "backup completed";
});
