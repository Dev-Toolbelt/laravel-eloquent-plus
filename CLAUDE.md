# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Run all tests
composer test

# Run a specific test file
vendor/bin/phpunit --configuration tests/phpunit.xml tests/Unit/YourTest.php

# Run a specific test method
vendor/bin/phpunit --configuration tests/phpunit.xml --filter testMethodName

# Run tests with coverage
composer test:coverage

# Code style check
composer phpcs

# Code style fix
composer phpcs:fix

# Static analysis
composer phpstan
```

## Architecture

This is a Laravel package (`dev-toolbelt/laravel-eloquent-plus`) that extends Eloquent's Model with additional functionality.

### Core Components

**ModelBase** (`src/ModelBase.php`) - Abstract base class that all models should extend. Provides:
- Automatic validation via `$rules` property (applied on creating/updating events)
- Lifecycle hooks: `beforeValidate()`, `beforeSave()`, `afterSave()`, `beforeDelete()`, `afterDelete()`
- Automatic type casting inferred from validation rules (boolean, integer, numeric→float, date, array, Enum)
- Built-in SoftDeletes with `deleted_at`, `deleted_by` tracking
- `toArray()` excludes `deleted_at` and `deleted_by` by default
- `fill()` handles camelCase to snake_case attribute conversion

**Blamable** (`src/Concerns/Blamable.php`) - Trait for automatic user audit tracking:
- Sets `created_by`, `updated_by` on create
- Sets `updated_by` on update
- Sets `deleted_by`, `updated_by` on soft delete
- Clears `deleted_by` and updates `updated_by` on restore
- Uses `auth()->user()->getAuthIdentifier()` for user ID

### Column Constants

ModelBase defines constants for standard columns that Blamable uses:
- `CREATED_AT`, `UPDATED_AT`, `DELETED_AT` (timestamps)
- `CREATED_BY`, `UPDATED_BY`, `DELETED_BY` (audit columns)

### Test Structure

- Unit tests: `tests/Unit/`
- Integration tests: `tests/Integration/` (use SQLite in-memory)
- Base classes: `tests/TestCase.php`, `tests/IntegrationTestCase.php`
