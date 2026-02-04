<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\Casts\UuidToIdCast;
use DevToolbelt\LaravelEloquentPlus\ModelBase;

class OrderModel extends ModelBase
{
    protected $table = 'orders';

    protected $fillable = [
        'product_id',
    ];

    protected array $rules = [];

    protected $casts = [
        'product_id' => UuidToIdCast::class . ':products',
    ];
}
