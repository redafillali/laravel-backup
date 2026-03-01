<?php

namespace Redaelfillali\LaravelBackup\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Redaelfillali\LaravelBackup\Helpers\BackupManager;

class BackupController
{
    public function run(Request $request): JsonResponse
    {
        $allowedTypes = ['full', 'files', 'database'];
        $type = $request->get('type', 'database');
        $path = $request->get('path', config('backup.path'));

        if (!in_array($type, $allowedTypes, true)) {
            return response()->json(['message' => 'Invalid backup type.'], 422);
        }

        try {
            BackupManager::backup($type, $path);
            return response()->json(['message' => 'Backup completed!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Backup failed: ' . $e->getMessage()], 500);
        }
    }
}
