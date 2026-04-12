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

This is a Laravel package (`dev-toolbelt/laravel-eloquent-plus`) that extends Eloquent's Model with additional functionality. Config namespace: `devToolbelt.eloquent-plus`.

### Core Components

**ModelBase** (`src/ModelBase.php`) - Abstract base class that all models should extend. Provides:
- Automatic validation via `$rules` property (applied on creating/updating events)
- Lifecycle hooks: `beforeValidate()`, `beforeSave()`, `afterSave()`, `beforeDelete()`, `afterDelete()`
- Automatic type casting inferred from validation rules (boolean, integer, numeric->float, date, array, Enum)
- Built-in SoftDeletes with `deleted_at`, `deleted_by` tracking
- `toArray()` excludes `deleted_at` and `deleted_by` by default (via `HasHiddenAttributes`)
- `fill()` handles camelCase to snake_case attribute conversion
- `getCreatedAtColumn()`/`getUpdatedAtColumn()` overrides return `null` when column not declared, preventing Laravel from auto-managing undeclared timestamp columns (including `Builder::addUpdatedAtColumn()`)
- `hasAttribute()` override checks `parent::hasAttribute()`, `$fillable`, and `$rules`
- `$addsRelationsInToArray` (default `false`): when `true`, `toArray()` replaces each loaded relation with its `toSoftArray()` form. `belongsTo` resolving to `null` is preserved as `null`; empty `hasMany` collections become `[]`; collection items not extending `ModelBase` fall back to default `toArray()`. Unloaded relations are not added.

**HasBlamable** (`src/Concerns/HasBlamable.php`) - Trait for automatic user audit tracking:
- Disabled by default (`$usesBlamable = false`), must be enabled per model
- Sets `created_by`, `updated_by` on create
- Sets `updated_by` on update
- Sets `deleted_by`, `updated_by` on soft delete (via `runSoftDelete()` override)
- Clears `deleted_by` and updates `updated_by` on restore
- Uses `auth()->user()->getAuthIdentifier()` for user ID
- Gracefully skips missing columns (controlled by `blamable_strict_mode` config)
- Supports configurable field type (`integer`/`string`) and custom value callable

**HasValidation** (`src/Concerns/HasValidation.php`) - Automatic validation:
- Validates on `creating` and `updating` events
- `autoPopulateFields()` pre-fills timestamps and blamable fields before validation, guarded by `hasAttribute()` checks for all fields (both timestamps and blamable)
- `setupRules()` auto-generates default rules for standard columns (PK, timestamps, blamable), only for columns that pass `hasAttribute()`
- Blamable rules use `buildForeignKeyRules()` which respects `blamable_field_type` config
- Throws `ValidationException` with detailed error info per field

### Key Traits

| Trait | File | Purpose |
|-------|------|---------|
| `HasBlamable` | `src/Concerns/HasBlamable.php` | User audit tracking (created_by, updated_by, deleted_by) |
| `HasValidation` | `src/Concerns/HasValidation.php` | Automatic model validation with rules |
| `HasExternalId` | `src/Concerns/HasExternalId.php` | UUID-based public identifiers |
| `HasAutoCasting` | `src/Concerns/HasAutoCasting.php` | Infer casts from validation rules |
| `HasDateFormatting` | `src/Concerns/HasDateFormatting.php` | Date output format control |
| `HasLifecycleHooks` | `src/Concerns/HasLifecycleHooks.php` | Model lifecycle callbacks |
| `HasHiddenAttributes` | `src/Concerns/HasHiddenAttributes.php` | Auto-hide sensitive fields |
| `HasCastAliases` | `src/Concerns/HasCastAliases.php` | Custom cast short names |

### Column Constants

ModelBase and HasBlamable define constants for standard columns:
- `CREATED_AT`, `UPDATED_AT`, `DELETED_AT` (timestamps, defined in ModelBase)
- `CREATED_BY`, `UPDATED_BY`, `DELETED_BY` (audit columns, defined in HasBlamable)

### Configuration (`config/eloquent-plus.php`)

| Option | Default | Description |
|--------|---------|-------------|
| `blamable_field_type` | `'integer'` | Type of blamable fields (`'integer'` or `'string'`) |
| `blamable_field_value` | `null` | Callable for custom user identifier (only for `'string'` type) |
| `blamable_strict_mode` | `false` | Throw `MissingModelPropertyException` if blamable columns missing |
| `timestamps_strict_mode` | `false` | Throw `MissingModelPropertyException` if timestamp columns missing |

### Exceptions

- `ValidationException` (`src/Exceptions/ValidationException.php`) - Thrown on validation failure, contains detailed error info per field
- `MissingModelPropertyException` (`src/Exceptions/MissingModelPropertyException.php`) - Thrown in strict mode when expected columns are missing
- `ExternalIdNotEnabledException` (`src/Exceptions/ExternalIdNotEnabledException.php`) - Thrown when calling external ID methods on models without it enabled

### Service Provider

`LaravelEloquentPlusServiceProvider` registers:
- Cast aliases: `only_numbers`, `remove_special_chars`, `uuid_to_id`
- Validators: `cpf_cnpj`, `cpf`, `cnpj`, `hex_color`
- Config from `config/eloquent-plus.php`

### Important Implementation Details

- **Trait boot order matters**: `HasBlamable` boots before `HasValidation` in ModelBase. The `creating`/`updating` events from `HasBlamable` fire before `HasValidation`'s events.
- **`autoPopulateFields()` and timestamps**: Both timestamps and blamable fields use `hasAttribute()` checks in `autoPopulateFields()`. Models must declare their columns in `$fillable`, `$rules`, or pre-populate `$attributes` for `hasAttribute()` to detect them. This enables partial column support — a model with `$timestamps = true` doesn't need all timestamp columns.
- **`hasAttribute()` relies on declared columns**: `hasAttribute()` checks `parent::hasAttribute()`, `$fillable`, and `$rules`. It does NOT check the database schema. Models must explicitly declare columns they use. During model construction, `hasAttribute()` may return `false` for columns that exist in the table but aren't declared in `$attributes`, `$fillable`, or `$rules`. This is by design.
- **Partial column support**: Both `$timestamps = true` and `$usesBlamable = true` support partial columns. A model can have only `created_at` without `updated_at`, or only `created_by` without `updated_by`/`deleted_by`. Undeclared columns are silently skipped. This is achieved via `getCreatedAtColumn()`/`getUpdatedAtColumn()` returning `null` for undeclared columns, which also prevents Laravel's `Builder::addUpdatedAtColumn()` from injecting `updated_at` into UPDATE queries.
- **`runSoftDelete()` override**: `HasBlamable::runSoftDelete` is used instead of `SoftDeletes::runSoftDelete` to include blamable columns in the soft delete query. Both `deleted_at`/`updated_at` and blamable columns are guarded by `hasAttribute()` checks, supporting partial column scenarios.
- **phpcs max line length**: 120 characters. Extract long conditions into variables if needed.

## Test Structure

- Unit tests: `tests/Unit/`
- Integration tests: `tests/Integration/` (use SQLite in-memory via Orchestra Testbench)
- Full coverage tests: `tests/Integration/Concerns/` (trait-specific deep tests)
- Fixtures: `tests/Fixtures/` (test models, enums, users)
- Base classes: `tests/TestCase.php` (unit), `tests/IntegrationTestCase.php` (integration with DB)
- Test tables defined in `IntegrationTestCase::createTestTables()`: `users`, `test_models`, `simple_models`, `external_id_models`, `products`, `orders`, `no_timestamps_models`, `only_created_at_models`, `only_updated_at_models`, `partial_blamable_models`, `only_deleted_by_models`, `no_deleted_at_models`
- Partial columns tests: `tests/Integration/PartialColumnsTest.php` (covers partial timestamps, partial blamable, runSoftDelete, autoPopulateFields, setupRules, and updateTimestamps with missing columns)
- Relations in toArray tests: `tests/Integration/AddsRelationsInToArrayTest.php` (covers `$addsRelationsInToArray` for belongsTo, hasMany, null relations, empty collections, disabled state, and unloaded relations)
