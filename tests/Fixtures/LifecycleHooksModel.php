<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

/**
 * Model for testing lifecycle hooks trait.
 *
 * Tracks when each lifecycle hook is called.
 */
class LifecycleHooksModel extends ModelBase
{
    protected $table = 'test_models';
    protected $guarded = [];

    /** @var array<string, int> */
    public array $hooksCalled = [
        'beforeValidate' => 0,
        'beforeSave' => 0,
        'afterSave' => 0,
        'beforeDelete' => 0,
        'afterDelete' => 0,
    ];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
    ];

    protected function beforeValidate(): void
    {
        $this->hooksCalled['beforeValidate']++;
    }

    protected function beforeSave(): void
    {
        $this->hooksCalled['beforeSave']++;
    }

    protected function afterSave(): void
    {
        $this->hooksCalled['afterSave']++;
    }

    protected function beforeDelete(): void
    {
        $this->hooksCalled['beforeDelete']++;
    }

    protected function afterDelete(): void
    {
        $this->hooksCalled['afterDelete']++;
    }
}
