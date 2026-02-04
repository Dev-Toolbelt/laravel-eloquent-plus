<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Fixtures;

use DevToolbelt\LaravelEloquentPlus\Concerns\HasBlamable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model that uses HasBlamable trait but does NOT define the blamable constants.
 * This is used to test the return null branches in getCreatedByColumn, getUpdatedByColumn, getDeletedByColumn.
 */
class BlamableWithoutConstantsModel extends Model
{
    use HasBlamable;
    use SoftDeletes;

    protected $table = 'simple_models';

    protected $fillable = [
        'title',
    ];

    /**
     * Expose protected method for testing.
     */
    public function testGetCreatedByColumn(): ?string
    {
        return $this->getCreatedByColumn();
    }

    /**
     * Expose protected method for testing.
     */
    public function testGetUpdatedByColumn(): ?string
    {
        return $this->getUpdatedByColumn();
    }

    /**
     * Expose protected method for testing.
     */
    public function testGetDeletedByColumn(): ?string
    {
        return $this->getDeletedByColumn();
    }
}
