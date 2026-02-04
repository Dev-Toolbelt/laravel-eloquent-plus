<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

use DevToolbelt\LaravelEloquentPlus\Exceptions\LaravelEloquentPlusException;
use Illuminate\Validation\Rules\Enum as ValidationEnum;
use ReflectionClass;

/**
 * Trait for automatic type casting based on validation rules.
 *
 * Infers and applies Eloquent casts automatically from validation rules:
 * - 'boolean' rule -> boolean cast
 * - 'integer' rule -> integer cast
 * - 'numeric' rule -> float cast
 * - 'date' or 'date_format' rules -> date/datetime cast
 * - 'array' rule -> array cast
 * - Enum validation rule -> enum class cast
 *
 * @package DevToolbelt\LaravelEloquentPlus\Concerns
 */
trait HasAutoCasting
{
    /**
     * Initialize the HasAutoCasting trait.
     *
     * @return void
     * @throws LaravelEloquentPlusException
     */
    protected function initializeHasAutoCasting(): void
    {
        $this->setupCasts();
    }

    /**
     * Set up automatic type casts based on validation rules.
     *
     * Infers the appropriate cast type from validation rules.
     * Custom casts defined in the model are merged after inferred casts,
     * allowing them to override automatic casts.
     *
     * @return void
     * @throws LaravelEloquentPlusException
     */
    private function setupCasts(): void
    {
        if (empty($this->getRules())) {
            return;
        }

        $defaultCasts = [];

        foreach ($this->rules as $attribute => $rules) {
            if (!is_array($rules)) {
                throw new LaravelEloquentPlusException(
                    'Use the list of validators in array format for validation by the model.'
                );
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

            $dateCast = $this->resolveDateCast($attribute, $rules);
            if ($dateCast !== null) {
                $defaultCasts[$attribute] = $dateCast;
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

        $this->casts = [...$defaultCasts, ...$this->casts];
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
