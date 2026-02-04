<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model for testing hidden attributes trait with pre-populated attributes.
 */
class HiddenAttributesModel extends ModelBase
{
    use SoftDeletes;

    protected $table = 'test_models';
    protected $guarded = [];

    /** @var array<string> */
    protected $hidden = ['secret_field'];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
    ];

    /**
     * Pre-populate attributes to ensure hasAttribute returns true for DELETED_AT and DELETED_BY.
     */
    public function __construct(array $attributes = [])
    {
        // Pre-set attributes before parent constructor
        $this->attributes = [
            'deleted_at' => null,
            'deleted_by' => null,
        ];

        parent::__construct($attributes);
    }
}
