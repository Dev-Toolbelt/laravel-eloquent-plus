<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast that removes all non-numeric characters from a string.
 *
 * Useful for fields like phone numbers, CPF, CNPJ, ZIP codes, etc.
 * where you want to store only the numeric digits.
 *
 * Usage in model:
 * ```php
 * protected $casts = [
 *     'phone' => OnlyNumbers::class,
 *     'cpf' => OnlyNumbers::class,
 *     'zip_code' => OnlyNumbers::class,
 * ];
 * ```
 *
 * Example:
 * - Input: "(11) 99999-9999" → Output: "11999999999"
 * - Input: "123.456.789-00" → Output: "12345678900"
 *
 * @package DevToolbelt\LaravelEloquentPlus\Casts
 */
final readonly class OnlyNumbers implements CastsAttributes
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
     * Removes all non-numeric characters from the value.
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

        return preg_replace('/\D/', '', (string) $value);
    }
}
