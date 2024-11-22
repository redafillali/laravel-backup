<?php

namespace Redaelfillali\LaravelBackup\Controllers;

use Illuminate\Http\Request;
use Redaelfillali\LaravelBackup\Helpers\BackupManager;

class BackupController
{
    public function run(Request $request)
    {
        $type = $request->get('type', 'full');
        $path = $request->get('path', config('backup.path'));

        BackupManager::backup($type, $path);

        return response()->json(['message' => 'Backup completed!']);
    }
}
