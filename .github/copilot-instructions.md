# Copilot Instructions for laravel-backup

## Repository Overview

`redaelfillali/laravel-backup` is a small Laravel package (not a full application) that provides database and file backup functionality for shared-hosting environments without relying on `proc_open`. It exposes an Artisan command, an HTTP controller, and routes that consumer applications can use.

- **Type**: Composer PHP package (Laravel extension)
- **Language**: PHP 8.x
- **Frameworks/Libraries**: Laravel (illuminate/support ^9|^10|^11), league/flysystem ^3.29, google/apiclient ^2.18
- **Size**: ~6 source files, no test suite, no CI pipeline for PHP

---

## Repository Layout

```
laravel-backup/
├── composer.json           # Package manifest; namespace: Redaelfillali\LaravelBackup
├── composer.lock           # Pinned dependency versions
├── readme.md               # Usage documentation
├── .gitignore              # Excludes /vendor/
└── src/
    ├── BackupServiceProvider.php        # Laravel service provider (registers command, publishes config, loads routes)
    ├── Commands/
    │   └── BackupCommand.php            # Artisan command: php artisan backup:run {type=full} {--path=}
    ├── Controllers/
    │   └── BackupController.php         # HTTP controller; POST /backup/run
    ├── Helpers/
    │   └── BackupManager.php            # Core backup logic (files zip + SQL dump via PDO)
    ├── config/
    │   └── backup.php                   # Default package config (path, types, retention, timeout, etc.)
    └── routes/
        └── web.php                      # Package HTTP routes: GET /backup/run, /backup/database, /backup/files
```

There are **no tests**, no `phpunit.xml`, and no PHP-specific GitHub Actions CI workflows.

---

## Build & Validation

### Runtime Versions
- PHP: 8.3.x (confirmed in environment)
- Composer: 2.x

### Installing Dependencies

Always run `composer install` before any validation. **Warning**: The `google/apiclient` dependency pulls in `google/apiclient-services` as a transitive dependency, which is very large (~400 MB download); expect `composer install` to take **5–10 minutes** on a fresh environment. Do not interrupt it.

```bash
composer install
```

There is no `--no-dev` distinction since `composer.json` has no `require-dev` section.

### No Tests

There is **no test suite** in this repository. There is no `phpunit.xml`, no `tests/` directory, and no test runner configured. Do not attempt to run `phpunit` or `php artisan test`.

### No Linting or Static Analysis

There is **no linting configuration** (no `.php-cs-fixer.php`, no `phpstan.neon`, no `pint.json`). Do not attempt to run code style or static analysis tools unless you have added them yourself.

### Validating PHP Syntax

A quick syntax check across all source files can be done with:

```bash
find src/ -name "*.php" -exec php -l {} \;
```

All files should report `No syntax errors detected`.

---

## Architecture & Key Patterns

- **Service Provider** (`src/BackupServiceProvider.php`): Registers the Artisan command, publishes `src/config/backup.php` to the host app's `config/` directory, and loads `src/routes/web.php`.
- **BackupManager** (`src/Helpers/BackupManager.php`): Static utility class. `BackupManager::backup(string $type, string $path)` is the central entry point. Type values are `'full'`, `'files'`, or `'database'`.
- **Config key**: `backup` (merged via `mergeConfigFrom`). Access with `config('backup.path')`, `config('backup.retention_period')`, etc.
- **Artisan command**: `backup:run {type=full} {--path=}` — triggers `BackupManager::backup()`.
- **Routes**: `GET /backup/run`, `GET /backup/database`, `GET /backup/files` — all invoke `BackupManager::backup()`.
- **Namespace**: `Redaelfillali\LaravelBackup` (PSR-4, autoloaded from `src/`).

---

## GitHub Workflows

The repository has no custom PHP test or lint CI workflow. The only active GitHub Actions are:
- **Copilot code review** (automated PR review)
- **Dependabot Updates** (dependency version bumps)

There are no pre-merge checks that run PHP validation, so the primary validation step is manual syntax checking (`php -l`).

---

## Key Facts for Making Changes

- Changes to `src/config/backup.php` affect defaults for host applications; always preserve existing config keys.
- The `BackupManager::backupDatabase()` method uses raw PDO (`DB::connection()->getPdo()`) and MySQL-specific SQL (`SHOW TABLES`, `SHOW CREATE TABLE`). Assume MySQL unless changed.
- The `google/apiclient` dependency is present in `composer.json` but **not used anywhere in the current source**. It was likely added for a planned Google Drive backup feature.
- No controller extends Laravel's base `Controller` class — `BackupController` is a plain class.
- Routes are loaded unconditionally (not only in console mode), so they are always registered in any host app using this package.

Trust these instructions. Only search the codebase if the information above appears incomplete or incorrect.
