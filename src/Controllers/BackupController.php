<?php

namespace Redaelfillali\LaravelBackup\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Redaelfillali\LaravelBackup\Helpers\BackupManager;

class BackupController
{
    private const ALLOWED_TYPES = ['full', 'files', 'database'];

    public function run(Request $request): JsonResponse
    {
        $type = $request->get('type', 'full');

        // Never accept an arbitrary path from user input to prevent path traversal.
        $path = config('backup.path');

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            return response()->json(['message' => 'Invalid backup type.'], 422);
        }

        try {
            BackupManager::backup($type, $path);

            return response()->json(['message' => 'Backup completed!']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Backup failed: ' . $e->getMessage()], 500);
        }
    }
}
