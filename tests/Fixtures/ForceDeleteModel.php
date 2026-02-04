<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

/**
 * Model for testing force delete behavior in HasBlamable.
 */
class ForceDeleteModel extends ModelBase
{
    protected $table = 'test_models';
    protected $guarded = [];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
    ];
}
