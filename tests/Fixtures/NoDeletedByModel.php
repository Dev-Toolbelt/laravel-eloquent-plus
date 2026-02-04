<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

/**
 * Model that returns null for getDeletedByColumn to test branch coverage.
 */
class NoDeletedByModel extends ModelBase
{
    protected $table = 'test_models';
    protected $guarded = [];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
    ];

    /**
     * Override to return null for testing purposes.
     */
    protected function getDeletedByColumn(): ?string
    {
        return null;
    }
}
