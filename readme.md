# redaelfillali/laravel-backup

A simple Laravel package for backing up files and databases with support for different backup types and scheduling. This package allows you to perform backups without the use of `proc_open`, and can be triggered via a console command or a custom controller route.

## Requirements

- PHP ^8.1
- Laravel ^10.0 | ^11.0 | ^12.0

## Features

- **Backup Path**: Easily set the destination folder for backups.
- **Backup Types**: 
  - Full Backup (both files and database)
  - Files Only
  - Database Only
- **No `proc_open` Dependency**: The package works without relying on `proc_open`, ensuring compatibility with restrictive hosting environments.
- **Command and Controller Support**: Can be used via a console command or integrated into your application with a custom controller and route.
- **Scheduled Backups**: Utilize Laravel's task scheduling system to automate backups.
- **Retention Policy**: Automatically removes backup files older than a configurable number of days.

## Installation

You can install this package via Composer:

```bash
composer require redaelfillali/laravel-backup
```

## Configuration

After installation, publish the configuration file using the following Artisan command:

```bash
php artisan vendor:publish --provider="Redaelfillali\LaravelBackup\BackupServiceProvider"
```

This will copy the configuration file to the `config/backup.php` file, where you can adjust settings such as the backup path, timeout, and retention period.

## Usage

### Running Backups via Command

You can run a backup using the following Artisan command:

```bash
php artisan backup:run
```

Specify a backup type (`full`, `files`, or `database`):

```bash
php artisan backup:run full
php artisan backup:run database
php artisan backup:run files
```

Override the backup path:

```bash
php artisan backup:run full --path=/your/custom/path
```

### Running Backups via Controller

You can trigger a backup by sending a POST request to the built-in routes or by calling `BackupManager` directly:

```php
use Redaelfillali\LaravelBackup\Helpers\BackupManager;

class BackupController extends Controller
{
    public function createBackup()
    {
        BackupManager::backup('full', config('backup.path'));

        return response()->json(['message' => 'Backup completed successfully']);
    }
}
```

### Built-in Routes

The package registers the following POST routes automatically:

| Method | URI               | Description              |
|--------|-------------------|--------------------------|
| POST   | `/backup/run`     | Run a backup (pass `type` and optional `path` in the request body) |
| POST   | `/backup/database`| Run a database-only backup |
| POST   | `/backup/files`   | Run a files-only backup  |

Example request using the `/backup/run` route:

```php
Route::post('/trigger-backup', function () {
    return Http::post(url('/backup/run'), ['type' => 'database']);
});
```

### Scheduling Backups

You can schedule backups using Laravel's task scheduling system. Add the following to your `App\Console\Kernel` class (Laravel 10) or `routes/console.php` (Laravel 11+):

**Laravel 10 — `app/Console/Kernel.php`:**

```php
use Illuminate\Console\Scheduling\Schedule;

protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:run database')
             ->daily();
}
```

**Laravel 11+ — `routes/console.php`:**

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:run database')->daily();
```

### Custom Route Definition

If you prefer to define your own route, you can do so by calling `BackupManager` directly:

```php
use Redaelfillali\LaravelBackup\Helpers\BackupManager;

Route::post('/backup', function () {
    BackupManager::backup('full', config('backup.path'));
    return response()->json(['message' => 'Backup completed successfully']);
})->middleware('auth');
```

> **Note:** It is strongly recommended to protect backup routes with authentication middleware to prevent unauthorised access.

## Configuration Reference

| Key                    | Default                          | Description                                         |
|------------------------|----------------------------------|-----------------------------------------------------|
| `path`                 | `storage/backups/Y/m/d`          | Destination directory for backup files              |
| `types`                | `['full', 'files', 'database']`  | Available backup types                              |
| `timeout`              | `3600`                           | Maximum execution time in seconds                   |
| `retention_period`     | `7`                              | Days to keep old backups before automatic deletion  |
| `backup_name`          | `backup-Y-m-d-H-i-s`            | Filename prefix for database backup files           |
| `enable_notifications` | `true`                           | Enable/disable backup notifications                 |

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
