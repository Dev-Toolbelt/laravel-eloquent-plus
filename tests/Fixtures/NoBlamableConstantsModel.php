<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

/**
 * Model that overrides getCreatedByColumn etc. to return null for testing.
 *
 * Since ModelBase defines the constants, we override the methods instead.
 */
class NoBlamableConstantsModel extends ModelBase
{
    protected $table = 'simple_models';

    protected $fillable = [
        'title',
    ];

    protected array $rules = [];

    protected bool $usesBlamable = false;

    /**
     * Override to return null for testing purposes.
     */
    protected function getCreatedByColumn(): ?string
    {
        return null;
    }

    /**
     * Override to return null for testing purposes.
     */
    protected function getUpdatedByColumn(): ?string
    {
        return null;
    }

    /**
     * Override to return null for testing purposes.
     */
    protected function getDeletedByColumn(): ?string
    {
        return null;
    }
}
