<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use Illuminate\Validation\Rules\Enum;

/**
 * Fake Enum rule that has no valid enum properties.
 *
 * Used for testing extractEnumCast returning null.
 */
class FakeEnumRule extends Enum
{
    /**
     * Override the type property to be a non-enum string.
     * The parent class uses an untyped property.
     *
     * @var string
     */
    protected $type = 'NotAnEnumClass';

    /**
     * Create a new fake enum rule.
     */
    public function __construct()
    {
        // Don't call parent constructor to avoid setting a real enum
    }
}
