<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Validators;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for hexadecimal color codes.
 *
 * Validates both short (3 characters) and full (6 characters) hex color formats.
 * The leading hash (#) is optional.
 *
 * Usage:
 * ```php
 * $rules = [
 *     'background_color' => ['required', new HexColor()],
 *     'text_color' => ['nullable', new HexColor()],
 * ];
 * ```
 *
 * Valid formats:
 * - "#FFF" or "FFF" (short format)
 * - "#FFFFFF" or "FFFFFF" (full format)
 * - Case insensitive: "#fff", "#AbC123"
 *
 * Invalid formats:
 * - "#FFFFF" (5 characters)
 * - "#GGG" (invalid hex characters)
 * - "red" (color names not supported)
 *
 * @package DevToolbelt\LaravelEloquentPlus\Validators
 */
final class HexColor implements ValidationRule
{
    /**
     * Regular expression pattern for valid hex color codes.
     *
     * Matches: #FFF, FFF, #FFFFFF, FFFFFF (case insensitive)
     */
    private const string PATTERN = '/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    /**
     * Run the validation rule.
     *
     * @param string $attribute The attribute name being validated
     * @param mixed $value The value to validate
     * @param Closure(string, string|null=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            $fail('The :attribute must be a valid hexadecimal color.');
            return;
        }

        if (!preg_match(self::PATTERN, $value)) {
            $fail('The :attribute must be a valid hexadecimal color (e.g., #FFF or #FFFFFF).');
        }
    }
}
