<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

/**
 * Model with date_format containing time component for testing datetime cast.
 */
class DateTimeFormatModel extends ModelBase
{
    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'published_at',
    ];

    protected array $rules = [
        'published_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
    ];
}
