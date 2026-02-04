<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

/**
 * Model that returns null for getUpdatedByColumn to test branch coverage.
 */
class NoUpdatedByModel extends ModelBase
{
    protected $table = 'test_models';
    protected $guarded = [];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
    ];

    protected bool $usesBlamable = false;

    /**
     * Override to return null for testing purposes.
     */
    protected function getUpdatedByColumn(): ?string
    {
        return null;
    }
}
