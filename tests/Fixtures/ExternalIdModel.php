<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\ModelBase;

class ExternalIdModel extends ModelBase
{
    protected $table = 'external_id_models';

    protected $fillable = [
        'name',
        'external_id',
    ];

    protected bool $usesExternalId = true;

    protected array $rules = [];
}
