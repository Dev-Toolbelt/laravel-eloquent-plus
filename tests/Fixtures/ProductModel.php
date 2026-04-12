<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductModel extends ModelBase
{
    public function orders(): HasMany
    {
        return $this->hasMany(OrderModel::class, 'product_id');
    }

    protected $table = 'products';

    protected $fillable = [
        'name',
        'external_id',
    ];

    protected bool $usesExternalId = true;

    protected array $rules = [];

    public function __construct(array $attributes = [])
    {
        // Pre-populate attributes to make hasAttribute return true
        $this->attributes = [
            'id' => null,
            'external_id' => null,
            'name' => null,
            'created_at' => null,
            'updated_at' => null,
            'deleted_at' => null,
        ];

        parent::__construct($attributes);
    }
}
