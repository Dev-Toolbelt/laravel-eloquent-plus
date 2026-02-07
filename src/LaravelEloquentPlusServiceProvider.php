<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus;

use DevToolbelt\LaravelEloquentPlus\Casts\OnlyNumbers;
use DevToolbelt\LaravelEloquentPlus\Casts\RemoveSpecialCharacters;
use DevToolbelt\LaravelEloquentPlus\Casts\UuidToIdCast;
use DevToolbelt\LaravelEloquentPlus\Validators\CpfCnpjValidator;
use DevToolbelt\LaravelEloquentPlus\Validators\HexColor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Laravel Eloquent Plus package.
 *
 * Registers custom validation rules and cast aliases for convenient usage.
 *
 * @package DevToolbelt\LaravelEloquentPlus
 */
class LaravelEloquentPlusServiceProvider extends ServiceProvider
{
    /**
     * Custom cast type aliases.
     *
     * Maps short names to their full cast class names.
     *
     * @var array<string, class-string>
     */
    public static array $castAliases = [
        'only_numbers' => OnlyNumbers::class,
        'remove_special_chars' => RemoveSpecialCharacters::class,
        'uuid_to_id' => UuidToIdCast::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/devToolbelt/eloquent-plus.php',
            'eloquent-plus'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/devToolbelt/eloquent-plus.php' => config_path('eloquent-plus.php'),
        ], 'eloquent-plus-config');

        $this->registerValidationRules();
        $this->registerCastAliases();
    }

    /**
     * Register custom validation rules with short aliases.
     *
     * After registration, you can use these rules like built-in validators:
     *
     * ```php
     * $rules = [
     *     'document' => ['required', 'cpf_cnpj'],
     *     'color' => ['nullable', 'hex_color'],
     * ];
     * ```
     */
    protected function registerValidationRules(): void
    {
        $cpfCnpjValidator = new CpfCnpjValidator();
        $hexColorValidator = new HexColor();

        // CPF/CNPJ Validator - validates Brazilian CPF or CNPJ documents
        Validator::extend(
            'cpf_cnpj',
            fn($attribute, $value) => $cpfCnpjValidator->passes($value),
            'The :attribute must be a valid CPF or CNPJ.'
        );

        // CPF only validator
        Validator::extend(
            'cpf',
            fn($attribute, $value) => $cpfCnpjValidator->passesCpf($value),
            'The :attribute must be a valid CPF.'
        );

        // CNPJ only validator
        Validator::extend(
            'cnpj',
            fn($attribute, $value) => $cpfCnpjValidator->passesCnpj($value),
            'The :attribute must be a valid CNPJ.'
        );

        // Hex Color validator
        Validator::extend(
            'hex_color',
            fn($attribute, $value) => $hexColorValidator->passes($value),
            'The :attribute must be a valid hex color.'
        );
    }

    /**
     * Register cast aliases for convenient usage in models.
     *
     * After registration, you can use short names in the $casts array:
     *
     * ```php
     * protected $casts = [
     *     'phone' => 'only_numbers',
     *     'name' => 'remove_special_chars',
     *     'category_id' => 'uuid_to_id:categories,external_id',
     * ];
     * ```
     */
    protected function registerCastAliases(): void
    {
        // Register cast aliases by extending the Model's cast types
        // This uses Laravel's castAttribute method override approach
        ModelBase::registerCastAliases(self::$castAliases);
    }

    /**
     * Get all registered cast aliases.
     *
     * @return array<string, class-string>
     */
    public static function getCastAliases(): array
    {
        return self::$castAliases;
    }
}
