<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Validators;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for Brazilian CPF (11 digits) and CNPJ (14 digits).
 *
 * Validates the mathematical check digits according to the official algorithm.
 * Automatically removes non-numeric characters before validation.
 *
 * Usage:
 * ```php
 * $rules = [
 *     'document' => ['required', new CpfCnpjValidator()],
 * ];
 * ```
 *
 * Accepts formats:
 * - CPF: "123.456.789-00" or "12345678900"
 * - CNPJ: "12.345.678/0001-00" or "12345678000100"
 *
 * @package DevToolbelt\LaravelEloquentPlus\Validators
 */
final class CpfCnpjValidator implements ValidationRule
{
    private const int CPF_LENGTH = 11;
    private const int CNPJ_LENGTH = 14;

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

        $value = $this->sanitize((string) $value);
        $length = strlen($value);

        if ($length === self::CPF_LENGTH) {
            if (!$this->validateCpf($value)) {
                $fail('The :attribute is not a valid CPF.');
            }
            return;
        }

        if ($length === self::CNPJ_LENGTH) {
            if (!$this->validateCnpj($value)) {
                $fail('The :attribute is not a valid CNPJ.');
            }
            return;
        }

        $fail('The :attribute must be a valid CPF (11 digits) or CNPJ (14 digits).');
    }

    /**
     * Remove all non-numeric characters from the value.
     *
     * @param string $value
     * @return string
     */
    private function sanitize(string $value): string
    {
        return (string) preg_replace('/\D/', '', trim($value));
    }

    /**
     * Validate a CPF number using the official algorithm.
     *
     * @param string $cpf The 11-digit CPF number
     * @return bool True if valid, false otherwise
     */
    private function validateCpf(string $cpf): bool
    {
        // Reject sequences of repeated digits (e.g., 111.111.111-11)
        if ((bool) preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Validate check digits
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;

            for ($c = 0; $c < $t; $c++) {
                $sum += (int) $cpf[$c] * (($t + 1) - $c);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a CNPJ number using the official algorithm.
     *
     * @param string $cnpj The 14-digit CNPJ number
     * @return bool True if valid, false otherwise
     */
    private function validateCnpj(string $cnpj): bool
    {
        // Reject sequences of repeated digits (e.g., 11.111.111/1111-11)
        if ((bool) preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Validate first check digit
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights[$i];
        }

        $digit = $sum % 11;
        $digit = $digit < 2 ? 0 : 11 - $digit;

        if ((int) $cnpj[12] !== $digit) {
            return false;
        }

        // Validate second check digit
        $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights[$i];
        }

        $digit = $sum % 11;
        $digit = $digit < 2 ? 0 : 11 - $digit;

        return (int) $cnpj[13] === $digit;
    }
}
