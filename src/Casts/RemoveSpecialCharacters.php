<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast that removes special characters from a string.
 *
 * Keeps only alphanumeric characters (a-z, A-Z, 0-9) and whitespace.
 * Useful for sanitizing user input for fields that should not contain
 * special characters like punctuation or symbols.
 *
 * Usage in model:
 * ```php
 * protected $casts = [
 *     'username' => RemoveSpecialCharacters::class,
 *     'slug' => RemoveSpecialCharacters::class,
 * ];
 * ```
 *
 * Example:
 * - Input: "Hello, World!" → Output: "Hello World"
 * - Input: "user@name#123" → Output: "username123"
 * - Input: "Test (value)" → Output: "Test value"
 *
 * @package DevToolbelt\LaravelEloquentPlus\Casts
 */
final readonly class RemoveSpecialCharacters implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * Returns the value as stored in the database.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     * @return string|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * Removes all special characters, keeping only alphanumeric and whitespace.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value === '' ? null : $value;
        }

        return preg_replace('/[^a-zA-Z0-9\s]/', '', (string) $value);
    }
}
