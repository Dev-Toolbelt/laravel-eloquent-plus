<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

/**
 * Trait that provides custom cast alias support for Eloquent models.
 *
 * Allows registering short alias names for cast classes, similar to
 * Laravel's built-in cast types like 'array', 'boolean', 'datetime', etc.
 *
 * Usage in Service Provider:
 * ```php
 * ModelBase::registerCastAliases([
 *     'only_numbers' => OnlyNumbers::class,
 *     'uuid_to_id' => UuidToIdCast::class,
 * ]);
 * ```
 *
 * Usage in Model:
 * ```php
 * protected $casts = [
 *     'phone' => 'only_numbers',
 *     'category_id' => 'uuid_to_id:categories,external_id',
 * ];
 * ```
 *
 * @package DevToolbelt\LaravelEloquentPlus\Concerns
 */
trait HasCastAliases
{
    /**
     * Custom cast type aliases.
     *
     * Maps short names to their full cast class names.
     *
     * @var array<string, class-string>
     */
    protected static array $castAliases = [];

    /**
     * Register custom cast aliases.
     *
     * @param array<string, class-string> $aliases Map of alias names to cast class names
     * @return void
     */
    public static function registerCastAliases(array $aliases): void
    {
        self::$castAliases = array_merge(self::$castAliases, $aliases);
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

    /**
     * Get the casts array with aliases resolved to their full class names.
     *
     * @return array<string, mixed>
     */
    public function getCasts(): array
    {
        $casts = parent::getCasts();

        foreach ($casts as $key => $castType) {
            if (!is_string($castType)) {
                continue;
            }

            // Extract the cast name (before any colon parameters)
            $castName = str_contains($castType, ':') ? explode(':', $castType, 2)[0] : $castType;

            if (isset(self::$castAliases[$castName])) {
                $castClass = self::$castAliases[$castName];

                // If the original cast type had parameters, append them to the class
                if (str_contains($castType, ':')) {
                    $parameters = explode(':', $castType, 2)[1];
                    $casts[$key] = $castClass . ':' . $parameters;
                } else {
                    $casts[$key] = $castClass;
                }
            }
        }

        return $casts;
    }
}
