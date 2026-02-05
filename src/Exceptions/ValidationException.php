<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Exceptions;

/**
 * Exception thrown when model validation fails.
 *
 * Contains detailed information about validation errors including
 * field names, error types, values, and messages.
 *
 * @package DevToolbelt\LaravelEloquentPlus\Exceptions
 */
class ValidationException extends LaravelEloquentPlusException
{
    /**
     * The validation errors.
     *
     * @var array<int, array{field: string, error: string, value: mixed, message: string|null}>
     */
    protected array $errors = [];

    /**
     * Create a new ValidationException instance.
     *
     * @param array<int, array{
     *     field: string,
     *     error: string,
     *     value: mixed,
     *     table: string|null,
     *     message: string|null
     * }> $errors
     * @param string $message
     */
    public function __construct(array $errors, string $message = 'The given data was invalid.')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /**
     * Get all validation errors.
     *
     * @return array<int, array{field: string, error: string, value: mixed, message: string|null}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation errors grouped by field.
     *
     * @return array<string, array<int, array{error: string, value: mixed, message: string|null}>>
     */
    public function getErrorsByField(): array
    {
        $grouped = [];

        foreach ($this->errors as $error) {
            $field = $error['field'];
            unset($error['field']);
            $grouped[$field][] = $error;
        }

        return $grouped;
    }

    /**
     * Get only the error messages grouped by field.
     *
     * @return array<string, array<int, string>>
     */
    public function getMessages(): array
    {
        $messages = [];

        foreach ($this->errors as $error) {
            if ($error['message'] !== null) {
                $messages[$error['field']][] = $error['message'];
            }
        }

        return $messages;
    }

    /**
     * Get the first error message for a specific field.
     *
     * @param string $field
     * @return string|null
     */
    public function getFirstMessageFor(string $field): ?string
    {
        foreach ($this->errors as $error) {
            if ($error['field'] === $field && $error['message'] !== null) {
                return $error['message'];
            }
        }

        return null;
    }

    /**
     * Check if a specific field has errors.
     *
     * @param string $field
     * @return bool
     */
    public function hasErrorFor(string $field): bool
    {
        foreach ($this->errors as $error) {
            if ($error['field'] === $field) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the list of fields that have errors.
     *
     * @return array<int, string>
     */
    public function getFailedFields(): array
    {
        return array_unique(array_column($this->errors, 'field'));
    }
}
