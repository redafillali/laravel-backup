<?php

use Redaelfillali\LaravelBackup\Controllers\BackupController;

Route::get('backup/run', [BackupController::class, 'run']);
