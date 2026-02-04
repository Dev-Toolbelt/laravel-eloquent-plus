<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\Casts\OnlyNumbers;
use DevToolbelt\LaravelEloquentPlus\ModelBase;
use Illuminate\Validation\Rules\Enum;

/**
 * Test model for casting tests without validation rules.
 */
class CastTestModel extends ModelBase
{
    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'document',
        'status',
        'is_active',
        'quantity',
        'price',
        'birth_date',
        'published_at',
        'metadata',
    ];

    protected $casts = [
        'phone' => OnlyNumbers::class,
    ];

    public function __construct(array $attributes = [])
    {
        // Set rules for auto-casting but not for validation
        $this->rules = [
            'status' => ['nullable', new Enum(TestStatus::class)],
            'is_active' => ['boolean'],
            'quantity' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'published_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];

        parent::__construct($attributes);

        // Clear rules after auto-casting is set up to avoid validation
        $this->rules = [];
    }
}
