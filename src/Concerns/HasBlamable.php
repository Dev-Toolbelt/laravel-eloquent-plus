<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

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
     * Boot the HasBlamable trait.
     *
     * Registers model event listeners to automatically set audit columns
     * on creating, updating, deleting, and restoring events.
     *
     * @return void
     */
    protected static function bootBlamable(): void
    {
        static::creating(static function (ModelBase $model): void {
            $userId = $model->getBlamableUserId();
            if ($userId === null) {
                return;
            }

            $createdBy = $model->getCreatedByColumn();
            $updatedBy = $model->getUpdatedByColumn();

            if ($createdBy !== null && $model->getAttribute($createdBy) === null) {
                $model->setAttribute($createdBy, $userId);
            }

            if ($updatedBy !== null && $model->getAttribute($updatedBy) === null) {
                $model->setAttribute($updatedBy, $userId);
            }
        });

        static::updating(static function (ModelBase $model): void {
            $userId = $model->getBlamableUserId();
            if ($userId === null) {
                return;
            }

            $updatedBy = $model->getUpdatedByColumn();
            if ($updatedBy !== null) {
                $model->setAttribute($updatedBy, $userId);
            }
        });

        static::deleting(static function (ModelBase $model): void {
            $userId = $model->getBlamableUserId();
            if ($userId === null) {
                return;
            }

            if ($model->usesSoftDeletes() && !$model->isForceDeleting()) {
                $deletedBy = $model->getDeletedByColumn();
                if ($deletedBy !== null) {
                    $model->setAttribute($deletedBy, $userId);
                }

                $updatedBy = $model->getUpdatedByColumn();
                if ($updatedBy !== null) {
                    $model->setAttribute($updatedBy, $userId);
                }
            }
        });

        static::restoring(static function (ModelBase $model): void {
            $userId = $model->getBlamableUserId();
            if ($userId === null) {
                return;
            }

            $deletedBy = $model->getDeletedByColumn();
            if ($deletedBy !== null) {
                $model->setAttribute($deletedBy, null);
            }

            $updatedBy = $model->getUpdatedByColumn();
            if ($updatedBy !== null) {
                $model->setAttribute($updatedBy, $userId);
            }
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
     * @return string|null The column name, or null if constant is not defined
     */
    protected function getCreatedByColumn(): ?string
    {
        if (defined(static::class . '::CREATED_BY')) {
            /** @var string */
            return constant(static::class . '::CREATED_BY');
        }

        return null;
    }

    /**
     * Get the name of the "updated by" column.
     *
     * Looks for the UPDATED_BY constant in the model class.
     *
     * @return string|null The column name, or null if constant is not defined
     */
    protected function getUpdatedByColumn(): ?string
    {
        if (defined(static::class . '::UPDATED_BY')) {
            /** @var string */
            return constant(static::class . '::UPDATED_BY');
        }

        return null;
    }

    /**
     * Get the name of the "deleted by" column.
     *
     * Looks for the DELETED_BY constant in the model class.
     *
     * @return string|null The column name, or null if constant is not defined
     */
    protected function getDeletedByColumn(): ?string
    {
        if (defined(static::class . '::DELETED_BY')) {
            /** @var string */
            return constant(static::class . '::DELETED_BY');
        }

        return null;
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
}
