<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

use DevToolbelt\LaravelEloquentPlus\Exceptions\MissingModelPropertyException;
use DevToolbelt\LaravelEloquentPlus\Exceptions\ValidationException;
use DevToolbelt\LaravelEloquentPlus\ModelBase;
use Throwable;

/**
 * Trait for automatic user audit tracking on Eloquent models.
 *
 * Automatically sets the created_by, updated_by, and deleted_by columns
 * based on the currently authenticated user. Works with Laravel's
 * authentication system via the auth() helper.
 *
 * Required constants in the model:
 * - CREATED_BY: Column name for tracking who created the record
 * - UPDATED_BY: Column name for tracking who last updated the record
 * - DELETED_BY: Column name for tracking who deleted the record (soft deletes)
 *
 * @package DevToolbelt\LaravelEloquentPlus\Concerns
 */
trait HasBlamable
{
    /**
     * The name of the "created by" column for audit tracking.
     */
    public const string CREATED_BY = 'created_by';

    /**
     * The name of the "updated by" column for audit tracking.
     */
    public const string UPDATED_BY = 'updated_by';

    /**
     * The name of the "deleted by" column for audit tracking.
     */
    public const string DELETED_BY = 'deleted_by';

    /**
     * Indicates if the model should use blamable (audit tracking).
     *
     * @var bool
     */
    protected bool $usesBlamable = false;

    /**
     * Boot the HasBlamable trait.
     *
     * Registers model event listeners to automatically set audit columns
     * on creating, updating, deleting, and restoring events.
     *
     * @return void
     * @throws ValidationException
     * @throws Throwable
     */
    protected static function bootHasBlamable(): void
    {
        static::creating(static function (ModelBase $model): void {
            if (!$model->usesBlamable()) {
                return;
            }

            $userId = $model->getBlamableUserId();
            if ($userId === null) {
                return;
            }

            $createdByColumn = $model->getCreatedByColumn();
            $updatedByColumn = $model->getUpdatedByColumn();
            $strictMode = config('devToolbelt.eloquent-plus.blamable_strict_mode', false);

            if (!$model->hasAttribute($createdByColumn)) {
                if ($strictMode) {
                    throw new MissingModelPropertyException($model::class, $createdByColumn);
                }
            } elseif (!$model->getAttribute($createdByColumn)) {
                $model->setAttribute($createdByColumn, $userId);
            }

            if (!$model->hasAttribute($updatedByColumn)) {
                if ($strictMode) {
                    throw new MissingModelPropertyException($model::class, $updatedByColumn);
                }
            } elseif (!$model->getAttribute($updatedByColumn)) {
                $model->setAttribute($updatedByColumn, $userId);
            }
        });

        static::updating(static function (ModelBase $model): void {
            if (!$model->usesBlamable()) {
                return;
            }

            $userId = $model->getBlamableUserId();
            if ($userId === null) {
                return;
            }

            $updatedByColumn = $model->getUpdatedByColumn();

            if (!$model->hasAttribute($updatedByColumn)) {
                if (config('devToolbelt.eloquent-plus.blamable_strict_mode', false)) {
                    throw new MissingModelPropertyException($model::class, $updatedByColumn);
                }
            } else {
                $model->setAttribute($updatedByColumn, $userId);
            }
        });

        static::deleting(static function (ModelBase $model): void {
            if (!$model->usesBlamable()) {
                return;
            }

            $userId = $model->getBlamableUserId();
            if ($userId === null) {
                return;
            }

            if ($model->usesSoftDeletes() && !$model->isForceDeleting()) {
                $deletedByColumn = $model->getDeletedByColumn();
                $updatedByColumn = $model->getUpdatedByColumn();
                $strictMode = config('devToolbelt.eloquent-plus.blamable_strict_mode', false);

                if (!$model->hasAttribute($deletedByColumn)) {
                    if ($strictMode) {
                        throw new MissingModelPropertyException($model::class, $deletedByColumn);
                    }
                } else {
                    $model->setAttribute($deletedByColumn, $userId);
                }

                if (!$model->hasAttribute($updatedByColumn)) {
                    if ($strictMode) {
                        throw new MissingModelPropertyException($model::class, $updatedByColumn);
                    }
                } else {
                    $model->setAttribute($updatedByColumn, $userId);
                }
            }
        });

        static::restoring(static function (ModelBase $model): void {
            if (!$model->usesBlamable()) {
                return;
            }

            $userId = $model->getBlamableUserId();
            if ($userId === null) {
                return;
            }

            $deletedByColumn = $model->getDeletedByColumn();
            $updatedByColumn = $model->getUpdatedByColumn();
            $strictMode = config('devToolbelt.eloquent-plus.blamable_strict_mode', false);

            if (!$model->hasAttribute($deletedByColumn)) {
                if ($strictMode) {
                    throw new MissingModelPropertyException($model::class, $deletedByColumn);
                }
            } else {
                $model->setAttribute($deletedByColumn, null);
            }

            if (!$model->hasAttribute($updatedByColumn)) {
                if ($strictMode) {
                    throw new MissingModelPropertyException($model::class, $updatedByColumn);
                }
            } else {
                $model->setAttribute($updatedByColumn, $userId);
            }
        });
    }

    /**
     * Get the authenticated user's identifier for audit tracking.
     *
     * When blamable_field_type is 'string':
     * - If blamable_field_value callable is set, it will be used to get the value
     * - Otherwise, the user ID will be cast to string
     *
     * @return int|string|null The user ID, or null if not authenticated
     */
    protected function getBlamableUserId(): int|string|null
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $fieldType = config('devToolbelt.eloquent-plus.blamable_field_type', 'integer');

        if ($fieldType === 'string') {
            $fieldValueCallable = config('devToolbelt.eloquent-plus.blamable_field_value');

            if (is_callable($fieldValueCallable)) {
                return $fieldValueCallable($user);
            }

            return (string) $user->getAuthIdentifier();
        }

        return $user->getAuthIdentifier();
    }

    /**
     * Get the name of the "created by" column.
     *
     * Looks for the CREATED_BY constant in the model class.
     *
     * @return string The column name, or null if constant is not defined
     */
    protected function getCreatedByColumn(): string
    {
        return $this::CREATED_BY;
    }

    /**
     * Get the name of the "updated by" column.
     *
     * Looks for the UPDATED_BY constant in the model class.
     *
     * @return string The column name, or null if constant is not defined
     */
    protected function getUpdatedByColumn(): string
    {
        return $this::UPDATED_BY;
    }

    /**
     * Get the name of the "deleted by" column.
     *
     * Looks for the DELETED_BY constant in the model class.
     *
     * @return string The column name, or null if constant is not defined
     */
    protected function getDeletedByColumn(): string
    {
        return $this::DELETED_BY;
    }

    /**
     * Determine if the model uses soft deletes.
     *
     * Checks for the presence of the isForceDeleting method,
     * which is added by the SoftDeletes trait.
     *
     * @return bool True if the model uses soft deletes, false otherwise
     */
    protected function usesSoftDeletes(): bool
    {
        return method_exists($this, 'isForceDeleting');
    }

    /**
     * Determine if the model uses blamable (audit tracking).
     *
     * @return bool True if the model uses blamable, false otherwise
     */
    public function usesBlamable(): bool
    {
        return $this->usesBlamable;
    }

    /**
     * Perform the actual soft delete query on the database.
     *
     * Overrides Laravel's runSoftDelete to include blamable columns
     * (deleted_by, updated_by) in the soft delete query.
     *
     * @return void
     * @throws MissingModelPropertyException
     */
    protected function runSoftDelete()
    {
        /** @phpstan-ignore argument.type */
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $columns = [];

        $deletedAtColumn = $this->getDeletedAtColumn();
        if ($this->hasAttribute($deletedAtColumn)) {
            $columns[$deletedAtColumn] = $this->fromDateTime($time);
            $this->{$deletedAtColumn} = $time;
        }

        $updatedAtColumn = $this->getUpdatedAtColumn();
        if (
            $this->usesTimestamps()
            && !is_null($updatedAtColumn)
            && $this->hasAttribute($updatedAtColumn)
        ) {
            $this->{$updatedAtColumn} = $time;
            $columns[$updatedAtColumn] = $this->fromDateTime($time);
        }

        // Include blamable columns if blamable is enabled
        if ($this->usesBlamable()) {
            $userId = $this->getBlamableUserId();

            if ($userId !== null) {
                $deletedByColumn = $this->getDeletedByColumn();
                $updatedByColumn = $this->getUpdatedByColumn();
                $strictMode = config('devToolbelt.eloquent-plus.blamable_strict_mode', false);

                if (!$this->hasAttribute($deletedByColumn)) {
                    if ($strictMode) {
                        throw new MissingModelPropertyException(static::class, $deletedByColumn);
                    }
                } else {
                    $columns[$deletedByColumn] = $userId;
                    $this->{$deletedByColumn} = $userId;
                }

                if (!$this->hasAttribute($updatedByColumn)) {
                    if ($strictMode) {
                        throw new MissingModelPropertyException(static::class, $updatedByColumn);
                    }
                } else {
                    $columns[$updatedByColumn] = $userId;
                    $this->{$updatedByColumn} = $userId;
                }
            }
        }

        $query->update($columns);

        /** @phpstan-ignore method.notFound */
        $this->syncOriginalAttributes(array_keys($columns));
    }
}
