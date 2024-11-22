<?php

use Redaelfillali\LaravelBackup\Controllers\BackupController;

Route::post('backup/run', [BackupController::class, 'run']);
