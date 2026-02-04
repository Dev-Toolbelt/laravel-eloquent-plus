# Laravel Eloquent Plus

[![CI](https://github.com/Dev-Toolbelt/laravel-eloquent-plus/actions/workflows/ci.yml/badge.svg)](https://github.com/Dev-Toolbelt/laravel-eloquent-plus/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/Dev-Toolbelt/laravel-eloquent-plus/branch/main/graph/badge.svg)](https://codecov.io/gh/Dev-Toolbelt/laravel-eloquent-plus)
[![Latest Stable Version](https://poser.pugx.org/dev-toolbelt/laravel-eloquent-plus/v/stable)](https://packagist.org/packages/dev-toolbelt/laravel-eloquent-plus)
[![Total Downloads](https://poser.pugx.org/dev-toolbelt/laravel-eloquent-plus/downloads)](https://packagist.org/packages/dev-toolbelt/laravel-eloquent-plus)
[![License](https://poser.pugx.org/dev-toolbelt/laravel-eloquent-plus/license)](https://packagist.org/packages/dev-toolbelt/laravel-eloquent-plus)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://php.net)

**Supercharge your Laravel Eloquent models** with automatic validation, audit trails, external IDs, smart casting, and lifecycle hooks — all with zero boilerplate.

---

## Features

| Feature | Description |
|---------|-------------|
| **Automatic Validation** | Validate model attributes before save using Laravel's validation rules |
| **Audit Trail (Blamable)** | Automatically track `created_by`, `updated_by`, and `deleted_by` |
| **External ID (UUID)** | Public-facing UUIDs while keeping internal integer IDs |
| **Smart Auto-Casting** | Infer attribute casts from validation rules automatically |
| **Date Formatting** | Control date output format (string or Carbon instance) |
| **Lifecycle Hooks** | Execute custom logic at `beforeValidate`, `beforeSave`, `afterSave`, `beforeDelete`, `afterDelete` |
| **Hidden Attributes** | Automatically hide sensitive fields like `deleted_at`, `deleted_by` |
| **Custom Validators** | Built-in CPF/CNPJ (Brazilian documents) and Hex Color validators |
| **Custom Casts** | `OnlyNumbers`, `RemoveSpecialCharacters`, `UuidToIdCast` |
| **Cast Aliases** | Register short names for custom casts like Laravel's built-in types |

---

## Requirements

- PHP ^8.3
- Laravel ^11.0

---

## Installation

```bash
composer require dev-toolbelt/laravel-eloquent-plus
```

The service provider is automatically registered via Laravel's package discovery.

---

## Quick Start

Extend your models from `ModelBase` to unlock all features:

```php
<?php

namespace App\Models;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

class Product extends ModelBase
{
    protected $fillable = ['name', 'price', 'sku'];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'price' => ['required', 'numeric', 'min:0'],
        'sku' => ['required', 'string', 'unique:products,sku'],
    ];
}
```

That's it! Your model now has:
- Automatic validation before create/update
- Audit trail (`created_by`, `updated_by`, `deleted_by`)
- Soft deletes with tracking
- External UUID for public APIs
- Smart type casting inferred from rules
- Lifecycle hooks ready to use

---

## Available Traits

Use traits individually if you don't want the full `ModelBase`:

| Trait | Description |
|-------|-------------|
| `HasValidation` | Automatic validation with rules and auto-population of timestamps/blamable |
| `HasBlamable` | Track who created, updated, and deleted records |
| `HasExternalId` | UUID-based public identifiers |
| `HasAutoCasting` | Infer casts from validation rules |
| `HasDateFormatting` | Control date attribute output format |
| `HasLifecycleHooks` | Model lifecycle callbacks |
| `HasHiddenAttributes` | Auto-hide sensitive fields |
| `HasCastAliases` | Register custom cast aliases |

```php
use Illuminate\Database\Eloquent\Model;
use DevToolbelt\LaravelEloquentPlus\Concerns\HasValidation;
use DevToolbelt\LaravelEloquentPlus\Concerns\HasBlamable;

class MyModel extends Model
{
    use HasValidation;
    use HasBlamable;

    // ...
}
```

---

## Validation

Define rules in your model and validation runs automatically:

```php
class User extends ModelBase
{
    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'document' => ['required', 'cpf_cnpj'], // Brazilian CPF/CNPJ
        'theme_color' => ['nullable', 'hex_color'],
    ];
}
```

### Built-in Validators

| Validator | Alias | Description |
|-----------|-------|-------------|
| `CpfCnpjValidator` | `cpf_cnpj` | Validates Brazilian CPF (11 digits) or CNPJ (14 digits) |
| `CpfCnpjValidator` | `cpf` | Validates only CPF |
| `CpfCnpjValidator` | `cnpj` | Validates only CNPJ |
| `HexColor` | `hex_color` | Validates hex color codes (#FFF or #FFFFFF) |

### Validation Exception

When validation fails, a `ValidationException` is thrown with detailed error information:

```php
use DevToolbelt\LaravelEloquentPlus\Exceptions\ValidationException;

try {
    $user->save();
} catch (ValidationException $e) {
    $e->getErrors();        // All errors as array
    $e->getMessages();      // All error messages
    $e->hasErrorFor('email'); // Check specific field
    $e->getFirstMessageFor('email'); // Get first error message
}
```

---

## Audit Trail (Blamable)

Track who performed actions on your records:

```php
class Post extends ModelBase
{
    // These columns are automatically populated:
    // - created_by: Set on create (authenticated user ID)
    // - updated_by: Set on create and update
    // - deleted_by: Set on soft delete
}
```

### Database Migration

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->timestamps();
    $table->softDeletes();

    // Blamable columns
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->foreignId('deleted_by')->nullable()->constrained('users');
});
```

### Customizing Column Names

Override the constants in your model:

```php
class Post extends ModelBase
{
    public const string CREATED_BY = 'author_id';
    public const string UPDATED_BY = 'editor_id';
    public const string DELETED_BY = 'remover_id';
}
```

---

## External ID (UUID)

Expose UUIDs publicly while keeping integer primary keys internally:

```php
class Order extends ModelBase
{
    // Enable external ID (enabled by default)
    public const bool USES_EXTERNAL_ID = true;
    public const string EXTERNAL_ID_COLUMN = 'external_id';
}
```

### Usage

```php
$order = Order::create(['total' => 99.99]);

// Internal ID (hidden from serialization)
$order->id; // 1

// External UUID (exposed in API responses)
$order->getExternalId(); // "550e8400-e29b-41d4-a716-446655440000"

// Find by external ID
$order = Order::findByExternalId('550e8400-e29b-41d4-a716-446655440000');
$order = Order::findByExternalIdOrFail('550e8400-e29b-41d4-a716-446655440000');

// API response automatically uses UUID as "id"
$order->toArray(); // ['id' => '550e8400-e29b-41d4-a716-446655440000', ...]
```

### Database Migration

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->uuid('external_id')->unique();
    $table->decimal('total', 10, 2);
    $table->timestamps();
});
```

---

## Auto-Casting

Types are automatically inferred from validation rules:

| Validation Rule | Inferred Cast |
|-----------------|---------------|
| `boolean` | `boolean` |
| `integer` | `integer` |
| `numeric` | `float` |
| `array` | `array` |
| `date` | `datetime` |
| `date_format:Y-m-d` | `date:Y-m-d` |
| `date_format:Y-m-d H:i:s` | `datetime` |
| `Illuminate\Validation\Rules\Enum` | Enum class |

```php
class Product extends ModelBase
{
    protected array $rules = [
        'active' => ['boolean'],      // Cast to boolean
        'quantity' => ['integer'],    // Cast to integer
        'price' => ['numeric'],       // Cast to float
        'tags' => ['array'],          // Cast to array
        'expires_at' => ['date'],     // Cast to datetime
    ];

    // No need to define $casts - it's automatic!
}
```

---

## Custom Casts

### Built-in Casts

| Cast | Alias | Description |
|------|-------|-------------|
| `OnlyNumbers` | `only_numbers` | Removes non-numeric characters |
| `RemoveSpecialCharacters` | `remove_special_chars` | Removes special characters |
| `UuidToIdCast` | `uuid_to_id` | Converts UUID to internal ID via lookup |

### Using Casts

```php
class Customer extends ModelBase
{
    protected $casts = [
        // Using aliases (short names)
        'phone' => 'only_numbers',
        'name' => 'remove_special_chars',
        'category_id' => 'uuid_to_id:categories,external_id',

        // Or using full class names
        'document' => \DevToolbelt\LaravelEloquentPlus\Casts\OnlyNumbers::class,
    ];
}
```

### UuidToIdCast

Convert external UUIDs to internal IDs automatically:

```php
// When you receive a UUID from the API
$order->category_id = '550e8400-e29b-41d4-a716-446655440000';

// It's automatically converted to the internal ID
$order->category_id; // 42 (the actual ID from categories table)
```

---

## Lifecycle Hooks

Execute custom logic at specific points:

```php
class Invoice extends ModelBase
{
    protected function beforeValidate(): void
    {
        // Normalize data before validation
        $this->number = strtoupper($this->number);
    }

    protected function beforeSave(): void
    {
        // Logic after validation, before database write
        $this->total = $this->calculateTotal();
    }

    protected function afterSave(): void
    {
        // Logic after persisting to database
        event(new InvoiceSaved($this));
    }

    protected function beforeDelete(): void
    {
        // Cleanup before deletion
        $this->items()->delete();
    }

    protected function afterDelete(): void
    {
        // Logic after deletion
        Cache::forget("invoice:{$this->id}");
    }
}
```

### Hook Execution Order

**On Create:**
```
autoPopulateFields() → beforeValidate() → validation → beforeSave() → INSERT → afterSave()
```

**On Update:**
```
autoPopulateFields() → beforeValidate() → validation → beforeSave() → UPDATE → afterSave()
```

**On Delete:**
```
beforeDelete() → DELETE → afterDelete()
```

---

## Date Formatting

Control how date attributes are returned:

```php
class Event extends ModelBase
{
    // Return dates as formatted strings (default)
    protected bool $carbonInstanceInFieldDates = false;

    // Or return Carbon instances
    protected bool $carbonInstanceInFieldDates = true;

    protected array $rules = [
        'starts_at' => ['required', 'date_format:Y-m-d H:i:s'],
        'ends_at' => ['required', 'date_format:Y-m-d H:i:s'],
    ];
}
```

```php
$event->starts_at; // "2024-01-15 10:00:00" (string, when $carbonInstanceInFieldDates = false)
$event->starts_at; // Carbon instance (when $carbonInstanceInFieldDates = true)
```

---

## Configuration

### ModelBase Constants

| Constant | Default | Description |
|----------|---------|-------------|
| `CREATED_AT` | `'created_at'` | Created timestamp column |
| `UPDATED_AT` | `'updated_at'` | Updated timestamp column |
| `DELETED_AT` | `'deleted_at'` | Soft delete timestamp column |
| `CREATED_BY` | `'created_by'` | Created by user column |
| `UPDATED_BY` | `'updated_by'` | Updated by user column |
| `DELETED_BY` | `'deleted_by'` | Deleted by user column |
| `USES_EXTERNAL_ID` | `true` | Enable/disable external UUID |
| `EXTERNAL_ID_COLUMN` | `'external_id'` | External ID column name |

### ModelBase Properties

| Property | Default | Description |
|----------|---------|-------------|
| `$timestamps` | `true` | Enable timestamps |
| `$dateFormat` | `'Y-m-d H:i:s.u'` | Database date format |
| `$snakeAttributes` | `false` | Snake case in serialization |
| `$carbonInstanceInFieldDates` | `false` | Return Carbon vs string for dates |

---

## Full Example

```php
<?php

namespace App\Models;

use DevToolbelt\LaravelEloquentPlus\ModelBase;
use App\Enums\OrderStatus;

class Order extends ModelBase
{
    protected $fillable = [
        'customer_id',
        'status',
        'total',
        'notes',
        'delivered_at',
    ];

    protected array $rules = [
        'customer_id' => ['required', 'uuid', 'exists:customers,external_id'],
        'status' => ['required', new \Illuminate\Validation\Rules\Enum(OrderStatus::class)],
        'total' => ['required', 'numeric', 'min:0'],
        'notes' => ['nullable', 'string', 'max:1000'],
        'delivered_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
    ];

    protected $casts = [
        'customer_id' => 'uuid_to_id:customers,external_id',
    ];

    protected function beforeSave(): void
    {
        if ($this->isDirty('status') && $this->status === OrderStatus::Delivered) {
            $this->delivered_at = now();
        }
    }
}
```

---

## Development

### Running Tests

```bash
composer test
```

### Running Tests with Coverage

```bash
composer test:coverage
```

### Code Style (PSR-12)

```bash
composer phpcs
composer phpcs:fix
```

### Static Analysis (PHPStan)

```bash
composer phpstan
```

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Standards

- Minimum **85% test coverage**
- PSR-12 coding standards
- PHPStan level 6 compliance

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

### Coverage Report

- **Dashboard:** [Codecov](https://codecov.io/gh/dev-toolbelt/laravel-eloquent-plus)
- **HTML Report:** [GitHub Pages](https://dev-toolbelt.github.io/laravel-eloquent-plus/)

## Credits

- [Kilderson Sena](https://github.com/dersonsena)
- [All Contributors](../../contributors)

---

Made with by [Dev Toolbelt](https://github.com/Dev-Toolbelt)
