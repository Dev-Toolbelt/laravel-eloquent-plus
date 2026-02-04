<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

/**
 * Model with all attributes pre-populated for testing setupRules and setupHidden.
 */
class FullAttributesModel extends ModelBase
{
    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'email',
    ];

    protected bool $usesBlamable = true;

    protected array $rules = [];

    public function __construct(array $attributes = [])
    {
        // Pre-populate attributes BEFORE parent constructor to make hasAttribute return true
        // during initialization (when setupRules is called)
        $this->attributes = [
            'id' => null,
            'external_id' => null,
            'name' => null,
            'created_at' => null,
            'updated_at' => null,
            'deleted_at' => null,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ];

        parent::__construct($attributes);
    }
}
