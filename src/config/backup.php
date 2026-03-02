<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Storage Path
    |--------------------------------------------------------------------------
    | Absolute path where backup files (ZIP + SQL) will be stored.
    | The directory is created automatically if it does not exist.
    |
    */
    'path' => storage_path('backups'),

    /*
    |--------------------------------------------------------------------------
    | Retention Period (days)
    |--------------------------------------------------------------------------
    | Backup files older than this number of days are automatically deleted
    | after each successful backup run.
    |
    */
    'retention_period' => 7,

    /*
    |--------------------------------------------------------------------------
    | Execution Timeout (seconds)
    |--------------------------------------------------------------------------
    | Maximum PHP execution time allowed for a single backup run.
    | Set to 0 for no limit.
    |
    */
    'timeout' => 3600,
];
