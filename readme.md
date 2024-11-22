# redaelfillali/laravel-backup

A simple Laravel package for backing up files and databases with support for different backup types and scheduling. This package allows you to perform backups without the use of `proc_open`, and can be triggered via a console command or a custom controller route.

## Features

- **Backup Path**: Easily set the destination folder for backups.
- **Backup Types**: 
  - Full Backup (both files and database)
  - Files Only
  - Database Only
- **No `proc_open` Dependency**: The package works without relying on `proc_open`, ensuring compatibility with restrictive hosting environments.
- **Command and Controller Support**: Can be used via a console command or integrated into your application with a custom controller and route.
- **Scheduled Backups**: Utilize Laravel's task scheduling system to automate backups.

## Installation

You can install this package via Composer:

```bash
composer require redaelfillali/laravel-backup
```

## Configuration

After installation, if the package includes configuration files, you can publish them using the following Artisan command:

```bash
php artisan vendor:publish --provider="Redaelfillali\LaravelBackup\BackupServiceProvider"
```

This will copy the configuration file to your config directory, where you can adjust settings like the backup path and notification options.

## Usage

### Running Backups via Command

You can run a backup using the following Artisan command:

```bash
php artisan backup:run
```

### Available Options:

- --filename: Specify a custom filename for the backup.
- --only-db: Backup only the database.
- --db-name=*: Specify which database(s) to backup.
- --only-files: Backup only files (no database).
- --only-to-disk=*: Specify which disk to use for the backup.
- --disable-notifications: Disable notifications after the backup.
- --timeout=*: Set a timeout for the backup process.
- --tries=*: Specify the number of retry attempts for the backup.

### Running Backups via Controller

You can also run a backup by sending a POST request to a custom route in your application. Here's an example of how you might set up a route and controller method:

```php
use Redaelfillali\LaravelBackup\Backup;

class BackupController extends Controller
{
    public function runBackup()
    {
        $backupService = new BackupService();

        // Trigger a full backup
        $backupService->runBackup();

        // Or trigger specific types of backups
        // $backupService->runBackup(['only_db' => true]); // Backup only database
        // $backupService->runBackup(['only_files' => true]); // Backup only files
    }
}
```

### Scheduling Backups

You can schedule backups using Laravel's task scheduling system. Here's an example of how you might set up a scheduled backup in your `App\Console\Kernel` class:

```php
use Illuminate\Console\Scheduling\Schedule;

protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:run')
             ->daily(); // Run the backup daily
}
```
 ### Controller Route for Backup

```php
namespace App\Http\Controllers;

use Redaelfillali\LaravelBackup\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function createBackup()
    {
        $backupService = new BackupService();
        $backupService->runBackup();

        return response()->json(['message' => 'Backup completed successfully']);
    }
}
```

### Route Definition

```php
Route::get('/backup', [BackupController::class, 'createBackup']);

```

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

### Key Sections:
1. **Installation**: Instructions for adding the package to your Laravel project via Composer.
2. **Configuration**: How to publish configuration files and modify settings.
3. **Usage**: 
   - How to run backups manually using an Artisan command.
   - Example code to trigger backups via a controller.
   - How to schedule backups with Laravel's task scheduler.
4. **Controller Route**: Instructions to set up an HTTP route to trigger backups via a controller.
5. **License**: Information about the open-source license for the package.

This `README.md` should give clear guidance to users on how to install, configure, and use your backup package within a Laravel project. Feel free to expand or adjust the sections as necessary!
