<?php

return [
  // Define the backup storage path
  'path' => storage_path('backups').'/'.date('Y').'/'.date('m').'/'.date('d'),

  // Define the types of backups that can be performed
  'types' => ['full', 'files', 'database'],

  // Database connection details (you can also retrieve these from .env or config/database.php)
  'database' => [
    'connection' => env('DB_CONNECTION', 'mysql'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', 3306),
    'database' => env('DB_DATABASE', 'your_database_name'),
    'username' => env('DB_USERNAME', 'your_database_user'),
    'password' => env('DB_PASSWORD', 'your_database_password'),
  ],

  // Path for storing database backups specifically
  'database_backup_path' => storage_path('backups/database'),

  // Path for storing file backups specifically
  'files_backup_path' => storage_path('backups/files'),

  // Command for mysqldump (make sure mysqldump is available on your system)
  'mysqldump_command' => env('MYSQLDUMP_COMMAND', 'mysqldump'),

  // Enable notifications after backup (can be handled later if needed)
  'enable_notifications' => true,

  // Specify if you want to disable notifications during the backup process
  'disable_notifications' => false,

  // Timeout for the backup process
  'timeout' => 3600, // 1 hour, adjust as necessary

  // Retry attempts if the backup fails
  'retry_attempts' => 3,
  // backup name
  'backup_name' => 'backup-'.date('Y-m-d-H-i-s'),

  // retention period for backups
  'retention_period' => 7, // 7 days
];
