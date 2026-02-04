<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\Casts\OnlyNumbers;
use DevToolbelt\LaravelEloquentPlus\ModelBase;
use Illuminate\Validation\Rules\Enum;

class TestModel extends ModelBase
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
        $this->rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'document' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(TestStatus::class)],
            'is_active' => ['boolean'],
            'quantity' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'published_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];

        parent::__construct($attributes);
    }

    public bool $beforeValidateCalled = false;
    public bool $beforeSaveCalled = false;
    public bool $afterSaveCalled = false;
    public bool $beforeDeleteCalled = false;
    public bool $afterDeleteCalled = false;

    protected function beforeValidate(): void
    {
        $this->beforeValidateCalled = true;
    }

    protected function beforeSave(): void
    {
        $this->beforeSaveCalled = true;
    }

    protected function afterSave(): void
    {
        $this->afterSaveCalled = true;
    }

    protected function beforeDelete(): void
    {
        $this->beforeDeleteCalled = true;
    }

    protected function afterDelete(): void
    {
        $this->afterDeleteCalled = true;
    }
}
