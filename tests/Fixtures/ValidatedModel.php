<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

class ValidatedModel extends ModelBase
{
    protected $table = 'simple_models';

    protected $fillable = [
        'title',
    ];

    protected array $rules = [
        'title' => ['required', 'string', 'max:255'],
    ];
}
