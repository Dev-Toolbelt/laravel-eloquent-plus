<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

/**
 * Trait for model lifecycle hooks.
 *
 * Provides hook methods that can be overridden in child classes
 * to execute custom logic at specific points in the model lifecycle:
 * - beforeValidate: Before validation runs
 * - beforeSave: After validation, before database write
 * - afterSave: After the model is persisted
 * - beforeDelete: Before the model is deleted
 * - afterDelete: After the model is deleted
 *
 * @package DevToolbelt\LaravelEloquentPlus\Concerns
 */
trait HasLifecycleHooks
{
    /**
     * Boot the HasLifecycleHooks trait.
     *
     * Registers model event listeners to trigger lifecycle hooks.
     *
     * @return void
     */
    protected static function bootHasLifecycleHooks(): void
    {
        static::creating(static function (self $model): void {
            $model->beforeSave();
        });

        static::updating(static function (self $model): void {
            $model->beforeSave();
        });

        static::created(static function (self $model): void {
            $model->afterSave();
        });

        static::updated(static function (self $model): void {
            $model->afterSave();
        });

        static::saved(static function (self $model): void {
            $model->afterSave();
        });

        static::deleting(static function (self $model): void {
            $model->beforeDelete();
        });

        static::deleted(static function (self $model): void {
            $model->afterDelete();
        });
    }

    /**
     * Hook executed before validation runs.
     *
     * Override this method in child classes to perform actions
     * before the model is validated (e.g., data normalization).
     *
     * @return void
     */
    protected function beforeValidate(): void
    {
    }

    /**
     * Hook executed before the model is saved (created or updated).
     *
     * Override this method in child classes to perform actions
     * after validation passes but before the database write.
     *
     * @return void
     */
    protected function beforeSave(): void
    {
    }

    /**
     * Hook executed after the model is saved (created or updated).
     *
     * Override this method in child classes to perform actions
     * after the model has been persisted to the database.
     *
     * @return void
     */
    protected function afterSave(): void
    {
    }

    /**
     * Hook executed before the model is deleted.
     *
     * Override this method in child classes to perform actions
     * before the model is removed (e.g., cleanup related data).
     *
     * @return void
     */
    protected function beforeDelete(): void
    {
    }

    /**
     * Hook executed after the model is deleted.
     *
     * Override this method in child classes to perform actions
     * after the model has been removed from the database.
     *
     * @return void
     */
    protected function afterDelete(): void
    {
    }
}
