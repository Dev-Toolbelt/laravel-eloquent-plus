<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

class ProductModel extends ModelBase
{
    protected $table = 'products';

    protected $fillable = [
        'name',
        'external_id',
    ];

    protected bool $usesExternalId = true;

    protected array $rules = [];
}
