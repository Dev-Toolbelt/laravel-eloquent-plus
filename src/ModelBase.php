<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus;

use DateTime;
use DateTimeZone;
use DevToolbelt\LaravelEloquentPlus\Concerns\Blamable;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum as ValidationEnum;
use ReflectionClass;

/**
 * Abstract base model class that extends Laravel's Eloquent Model.
 *
 * Provides additional functionality including:
 * - Automatic validation based on defined rules
 * - Lifecycle hooks (beforeValidate, beforeSave, afterSave, beforeDelete, afterDelete)
 * - Automatic type casting inferred from validation rules
 * - Built-in soft deletes with user tracking (blamable)
 * - Automatic snake_case attribute conversion on fill
 *
 * @package DevToolbelt\LaravelEloquentPlus
 */
abstract class ModelBase extends Model
{
    use HasFactory;
    use HasEvents;
    use SoftDeletes;
    use Blamable;

    /**
     * The name of the "created at" column.
     */
    public const string CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     */
    public const string UPDATED_AT = 'updated_at';

    /**
     * The name of the "deleted at" column.
     */
    public const string DELETED_AT = 'deleted_at';

    /**
     * The name of the "created by" column for audit tracking.
     */
    public const string CREATED_BY = 'created_by';

    /**
     * The name of the "updated by" column for audit tracking.
     */
    public const string UPDATED_BY = 'updated_by';

    /**
     * The name of the "deleted by" column for audit tracking.
     */
    public const string DELETED_BY = 'deleted_by';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Indicates whether attributes are snake cased on arrays.
     *
     * @var bool
     */
    public static $snakeAttributes = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The validation rules for the model attributes.
     *
     * Rules are automatically merged with default rules for common columns
     * (primary key, timestamps, audit columns) during initialization.
     *
     * @var array<string, array<int, mixed>|string>
     */
    protected array $rules = [];

    /**
     * Initialize the ModelBase trait.
     *
     * Called automatically by Laravel's trait initialization mechanism.
     * Sets up validation rules, automatic casts, and hidden attributes.
     *
     * @return void
     */
    protected function initializeModelBase(): void
    {
        $this->setupRules();
        $this->setupCasts();
        $this->setupHidden();
    }

    /**
     * Boot the model and register lifecycle event callbacks.
     *
     * Registers callbacks for creating, created, updating, updated,
     * deleting, deleted, and saved events to trigger validation
     * and lifecycle hooks.
     *
     * @return void
     */
    protected static function booted(): void
    {
        $beforeSaveCallback = static function (self $model) {
            $model->beforeValidate();

            if (empty($model->rules)) {
                $model->beforeSave();
                return;
            }

            $attributes = $model->getAttributes();
            $validator = Validator::make($attributes, $model->rules);

            if ($validator->passes()) {
                $model->beforeSave();
                return;
            }

            $errors = [];
            $messages = $validator->errors()->toArray();

            foreach ($validator->failed() as $field => $failedRules) {
                $i = 0;
                $fieldMessages = $messages[$field] ?? [];

                foreach ($failedRules as $ruleName => $params) {
                    $errors[] = [
                        'field' => $field,
                        'error' => strtolower($ruleName),
                        'value' => $attributes[$field] ?? null,
                        'message' => $fieldMessages[$i] ?? null,
                    ];
                    $i++;
                }
            }

            // throw new ValidationException($details, $message);
        };

        $afterSaveCallback = static function (self $model) {
            $model->afterSave();
        };

        $beforeDeleteCallback = static function (self $model) {
            $model->beforeDelete();
        };

        $afterDeleteCallback = static function (self $model) {
            $model->afterDelete();
        };

        self::creating($beforeSaveCallback);
        self::created($afterSaveCallback);
        self::updating($beforeSaveCallback);
        self::updated($afterSaveCallback);
        self::deleting($beforeDeleteCallback);
        self::deleted($afterDeleteCallback);
        self::saved($afterSaveCallback);
    }

    /**
     * Set up default validation rules based on model attributes.
     *
     * Automatically adds validation rules for common columns if they exist:
     * - Primary key: nullable integer
     * - external_id: required UUID string with 36 characters
     * - Timestamp columns: date validation
     * - Audit columns (created_by, updated_by, deleted_by): integer with exists rule
     *
     * Custom rules defined in the model are merged after default rules,
     * allowing them to override defaults.
     *
     * @return void
     */
    private function setupRules(): void
    {
        $usersTable = 'users'; // how to get dynamically ?
        $defaultRules = [];

        if ($this->hasAttribute($this->primaryKey)) {
            $defaultRules[$this->primaryKey] = ['nullable', 'integer'];
        }

        if ($this->hasAttribute('external_id')) {
            $defaultRules['external_id'] = ['required', 'uuid', 'string', 'size:36'];
        }

        if ($this->hasAttribute(self::CREATED_AT)) {
            $defaultRules[self::CREATED_AT] = ['required', 'date'];
        }

        if ($this->hasAttribute(self::CREATED_BY)) {
            $defaultRules[self::CREATED_BY] = ['required', 'integer', "exists:{$usersTable},id"];
        }

        if ($this->hasAttribute(self::UPDATED_AT)) {
            $defaultRules[self::UPDATED_AT] = ['nullable', 'date'];
        }

        if ($this->hasAttribute(self::UPDATED_BY)) {
            $defaultRules[self::UPDATED_BY] = ['nullable', 'integer', "exists:{$usersTable},id"];
        }

        if ($this->hasAttribute(self::DELETED_AT)) {
            $defaultRules[self::DELETED_AT] = ['nullable', 'date'];
        }

        if ($this->hasAttribute(self::DELETED_BY)) {
            $defaultRules[self::DELETED_BY] = ['nullable', 'integer', "exists:{$usersTable},id"];
        }

        $this->rules = array_unique([...$defaultRules, ...$this->rules]);
    }

    /**
     * Set up automatic type casts based on validation rules.
     *
     * Infers the appropriate cast type from validation rules:
     * - 'boolean' rule -> boolean cast
     * - 'integer' rule -> integer cast
     * - 'numeric' rule -> float cast
     * - 'date' rule -> date cast
     * - 'array' rule -> array cast
     * - Enum validation rule -> enum class cast
     *
     * Custom casts defined in the model are merged after inferred casts,
     * allowing them to override automatic casts.
     *
     * @return void
     */
    private function setupCasts(): void
    {
        $defaultCasts = [];

        foreach ($this->rules as $attribute => $rules) {
            if (!is_array($rules)) {
                continue;
            }

            if (in_array('boolean', $rules, true)) {
                $defaultCasts[$attribute] = 'boolean';
            }

            if (in_array('integer', $rules, true)) {
                $defaultCasts[$attribute] = 'integer';
            }

            if (in_array('numeric', $rules, true)) {
                $defaultCasts[$attribute] = 'float';
            }

            if (in_array('date', $rules, true)) {
                $defaultCasts[$attribute] = 'date';
            }

            if (in_array('array', $rules, true)) {
                $defaultCasts[$attribute] = 'array';
            }

            foreach ($rules as $rule) {
                if ($rule instanceof ValidationEnum) {
                    $enumClass = $this->extractEnumCast($rule);
                    if ($enumClass !== null) {
                        $defaultCasts[$attribute] = $enumClass;
                    }
                }
            }
        }

        $this->casts = array_unique([...$defaultCasts, ...$this->casts]);
    }

    /**
     * Set up default hidden attributes for serialization.
     *
     * Automatically hides soft delete columns (deleted_at and deleted_by)
     * from array/JSON output. Custom hidden attributes defined in the model
     * are preserved and merged with these defaults.
     *
     * @return void
     */
    private function setupHidden(): void
    {
        $defaultHidden = [];

        if ($this->hasAttribute(static::DELETED_AT)) {
            $defaultHidden[] = static::DELETED_AT;
        }

        if ($this->hasAttribute(static::DELETED_BY)) {
            $defaultHidden[] = static::DELETED_BY;
        }

        $this->hidden = array_unique([...$defaultHidden, ...$this->hidden]);
    }

    /**
     * Hook executed before validation runs.
     *
     * Override this method in child classes to perform actions
     * before the model is validated (e.g., data normalization).
     *
     * @return void
     */
    protected function beforeValidate(): void
    {
    }

    /**
     * Hook executed before the model is saved (created or updated).
     *
     * Override this method in child classes to perform actions
     * after validation passes but before the database write.
     *
     * @return void
     */
    protected function beforeSave(): void
    {
    }

    /**
     * Hook executed after the model is saved (created or updated).
     *
     * Override this method in child classes to perform actions
     * after the model has been persisted to the database.
     *
     * @return void
     */
    protected function afterSave(): void
    {
    }

    /**
     * Hook executed before the model is deleted.
     *
     * Override this method in child classes to perform actions
     * before the model is removed (e.g., cleanup related data).
     *
     * @return void
     */
    protected function beforeDelete(): void
    {
    }

    /**
     * Hook executed after the model is deleted.
     *
     * Override this method in child classes to perform actions
     * after the model has been removed from the database.
     *
     * @return void
     */
    protected function afterDelete(): void
    {
    }

    /**
     * Fill the model with an array of attributes.
     *
     * Extends the default fill behavior to automatically convert
     * camelCase attribute names to snake_case before filling.
     *
     * @param array<string, mixed> $attributes Key-value pairs of attributes to fill
     * @return static
     */
    public function fill(array $attributes): self
    {
        if (empty($attributes)) {
            return parent::fill($attributes);
        }

        foreach ($attributes as $attributeName => $value) {
            $snakeCaseAttr = Str::snake($attributeName);

            if (!$this->hasAttribute($snakeCaseAttr)) {
                continue;
            }

            $attributes[$snakeCaseAttr] = $value;
        }

        return parent::fill($attributes);
    }

    /**
     * Convert the model to a minimal array representation.
     *
     * Returns only the primary key field, useful for references
     * or lightweight serialization.
     *
     * @return array<string, mixed> Array containing only the primary key
     */
    public function toSoftArray(): array
    {
        return $this->returnOnlyFields([$this->primaryKey]);
    }

    /**
     * Filter the model's array representation to include only specified fields.
     *
     * @param string[] $fieldsToReturn List of field names to include in the output
     * @return array<string, mixed> Filtered array containing only the specified fields
     */
    protected function returnOnlyFields(array $fieldsToReturn): array
    {
        return array_filter($this->toArray(), function ($key) use ($fieldsToReturn) {
            return in_array($key, $fieldsToReturn);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Resolve a datetime value to a formatted string.
     *
     * Converts Carbon instances or date strings to a consistent format.
     * Returns 'Y-m-d' format if time is midnight, otherwise 'Y-m-d H:i:s.u'.
     *
     * @param Carbon|string $value The datetime value to resolve
     * @return string The formatted datetime string
     *
     * @throws \DateInvalidTimeZoneException If the configured timezone is invalid
     * @throws \DateMalformedStringException If the date string cannot be parsed
     */
    private function resolveDateTime(Carbon|string $value): string
    {
        if ($value instanceof Carbon) {
            $value = $value->format('Y-m-d H:i:s');
        }

        $dateTime = new DateTime($value, new DateTimeZone(config('app.timezone')));
        $valueWithMs = $dateTime->format("Y-m-d H:i:s.u");
        [, $time] = explode(' ', $valueWithMs);

        if ($time === '00:00:00.000000') {
            return $dateTime->format('Y-m-d');
        }

        return $dateTime->format('Y-m-d H:i:s.u');
    }

    /**
     * Extract the enum class name from a ValidationEnum rule.
     *
     * Uses reflection to access the protected type property of the
     * Illuminate\Validation\Rules\Enum class.
     *
     * @param ValidationEnum $rule The enum validation rule instance
     * @return class-string|null The fully qualified enum class name, or null if not found
     */
    private function extractEnumCast(ValidationEnum $rule): ?string
    {
        $reflection = new ReflectionClass($rule);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($rule);

            if (is_string($value) && enum_exists($value)) {
                return $value;
            }
        }

        return null;
    }
}
