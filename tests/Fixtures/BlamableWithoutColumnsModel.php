<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

/**
 * Model that extends ModelBase with blamable enabled but uses a table
 * WITHOUT blamable columns (created_by, updated_by, deleted_by).
 * Used to test that blamable gracefully skips when columns don't exist.
 */
class BlamableWithoutColumnsModel extends ModelBase
{
    protected $table = 'simple_models';

    protected bool $usesBlamable = true;

    protected $fillable = [
        'title',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected array $rules = [
        'title' => ['required', 'string', 'max:255'],
    ];
}
