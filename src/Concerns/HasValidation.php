<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

use DevToolbelt\LaravelEloquentPlus\Exceptions\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Trait for automatic model validation based on defined rules.
 *
 * Provides validation functionality that runs before save operations.
 * Rules can be defined in the model and are automatically merged with
 * default rules for common columns.
 *
 * @package DevToolbelt\LaravelEloquentPlus\Concerns
 */
trait HasValidation
{
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
     * Boot the HasValidation trait.
     *
     * Registers model event listeners to validate attributes
     * before creating and updating operations.
     *
     * @return void
     * @throws ValidationException
     */
    protected static function bootHasValidation(): void
    {
        $validateCallback = static function (self $model): void {
            $model->beforeValidate();

            if (empty($model->rules)) {
                return;
            }

            $attributes = $model->getAttributes();
            $validator = Validator::make($attributes, $model->rules);

            if ($validator->passes()) {
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

            throw new ValidationException($errors);
        };

        static::creating($validateCallback);
        static::updating($validateCallback);
    }

    /**
     * Initialize the HasValidation trait.
     *
     * @return void
     */
    protected function initializeHasValidation(): void
    {
        $this->setupRules();
    }

    /**
     * Set up default validation rules based on model attributes.
     *
     * Automatically adds validation rules for common columns if they exist:
     * - Primary key: nullable integer
     * - external_id: required UUID string with 36 characters
     * - Timestamp columns: date validation
     * - Audit columns (created_by, updated_by, deleted_by): integer/uuid with exists rule
     *
     * Custom rules defined in the model are merged after default rules,
     * allowing them to override defaults.
     *
     * @return void
     */
    private function setupRules(): void
    {
        $usersTable = $this->getUsersTable();
        $defaultRules = [];

        if ($this->hasAttribute($this->primaryKey)) {
            $defaultRules[$this->primaryKey] = ['nullable', 'integer'];
        }

        if ($this->usesExternalId()) {
            $column = $this->getExternalIdColumn();
            $defaultRules[$column] = ['required', 'uuid', 'string', 'size:36'];
        }

        if ($this->hasAttribute(self::CREATED_AT)) {
            $defaultRules[self::CREATED_AT] = ['required', 'date'];
        }

        if ($this->hasAttribute(self::CREATED_BY)) {
            $defaultRules[self::CREATED_BY] = $this->buildForeignKeyRules($usersTable);
        }

        if ($this->hasAttribute(self::UPDATED_AT)) {
            $defaultRules[self::UPDATED_AT] = ['nullable', 'date'];
        }

        if ($this->hasAttribute(self::UPDATED_BY)) {
            $defaultRules[self::UPDATED_BY] = $this->buildForeignKeyRules($usersTable, false);
        }

        if ($this->hasAttribute(self::DELETED_AT)) {
            $defaultRules[self::DELETED_AT] = ['nullable', 'date'];
        }

        if ($this->hasAttribute(self::DELETED_BY)) {
            $defaultRules[self::DELETED_BY] = $this->buildForeignKeyRules($usersTable, false);
        }

        $this->rules = [...$defaultRules, ...$this->rules];
    }

    /**
     * Build validation rules for a foreign key field.
     *
     * When external ID is enabled, validates as UUID with exists check on external_id column.
     * When disabled, validates as integer with exists check on primary key column.
     *
     * @param string $table The related table name
     * @param bool $required Whether the field is required
     * @return array<int, string>
     */
    private function buildForeignKeyRules(string $table, bool $required = true): array
    {
        $requiredRule = $required ? 'required' : 'nullable';

        if ($this->usesExternalId()) {
            $column = $this->getExternalIdColumn();
            return [$requiredRule, 'uuid', 'string', "exists:{$table},{$column}"];
        }

        return [$requiredRule, 'integer', "exists:{$table},{$this->primaryKey}"];
    }

    /**
     * Get the validation rules for the model.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get the users table name from Laravel Auth configuration.
     *
     * Attempts to resolve the table name from the configured User model.
     * Falls back to 'users' if the model is not configured or doesn't exist.
     *
     * @return string
     */
    protected function getUsersTable(): string
    {
        /** @var class-string|null $userModel */
        $userModel = config('auth.providers.users.model');

        if ($userModel !== null && class_exists($userModel)) {
            return (new $userModel())->getTable();
        }

        return 'users';
    }
}
