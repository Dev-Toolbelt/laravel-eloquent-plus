<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

use DevToolbelt\LaravelEloquentPlus\Exceptions\MissingModelPropertyException;
use DevToolbelt\LaravelEloquentPlus\ModelBase;

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
     * @throws MissingModelPropertyException
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

            if (!$model->getAttribute($model->getCreatedByColumn())) {
                throw new MissingModelPropertyException($model::class, $model->getCreatedByColumn());
            }

            if (!$model->getAttribute($model->getUpdatedByColumn())) {
                throw new MissingModelPropertyException($model::class, $model->getUpdatedByColumn());
            }

            $model->setAttribute($model->getCreatedByColumn(), $userId);
            $model->setAttribute($model->getUpdatedByColumn(), $userId);
        });

        static::updating(static function (ModelBase $model): void {
            if (!$model->usesBlamable()) {
                return;
            }

            $userId = $model->getBlamableUserId();
            if ($userId === null) {
                return;
            }

            if (!$model->getAttribute($model->getUpdatedByColumn())) {
                throw new MissingModelPropertyException($model::class, $model->getUpdatedByColumn());
            }

            $model->setAttribute($model->getUpdatedByColumn(), $userId);
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
                if (!$model->getAttribute($model->getDeletedByColumn())) {
                    throw new MissingModelPropertyException($model::class, $model->getDeletedByColumn());
                }

                if (!$model->getAttribute($model->getUpdatedByColumn())) {
                    throw new MissingModelPropertyException($model::class, $model->getUpdatedByColumn());
                }

                $model->setAttribute($model->getDeletedByColumn(), $userId);
                $model->setAttribute($model->getUpdatedByColumn(), $userId);
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

            if (!$model->getAttribute($model->getDeletedByColumn())) {
                throw new MissingModelPropertyException($model::class, $model->getDeletedByColumn());
            }

            if (!$model->getAttribute($model->getUpdatedByColumn())) {
                throw new MissingModelPropertyException($model::class, $model->getUpdatedByColumn());
            }

            $model->setAttribute($model->getDeletedByColumn(), null);
            $model->setAttribute($model->getUpdatedByColumn(), $userId);
        });
    }

    /**
     * Get the authenticated user's identifier for audit tracking.
     *
     * @return int|string|null The user ID, or null if not authenticated
     */
    protected function getBlamableUserId(): int|string|null
    {
        return auth()->user()?->getAuthIdentifier();
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
}
