<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Trait for optional external ID (UUID) support on Eloquent models.
 *
 * Provides functionality to automatically generate and manage an external UUID
 * identifier separate from the primary key. This is useful for exposing
 * public-facing identifiers without revealing internal auto-increment IDs.
 *
 * @package DevToolbelt\LaravelEloquentPlus\Concerns
 */
trait HasExternalId
{
    /**
     * The name of the external ID column.
     *
     * @var string
     */
    protected string $externalIdColumn = 'external_id';

    /**
     * Indicates if the model uses an external ID column.
     *
     * When true, a UUID will be automatically generated on model creation.
     *
     * @var bool
     */
    protected bool $usesExternalId = false;

    /**
     * Boot the HasExternalId trait.
     *
     * Registers a creating event listener to automatically generate
     * a UUID for the external ID column if enabled.
     *
     * @return void
     */
    protected static function bootHasExternalId(): void
    {
        static::creating(static function (self $model): void {
            if (!$model->usesExternalId()) {
                return;
            }

            $column = $model->getExternalIdColumn();

            if ($model->getAttribute($column) === null) {
                $model->setAttribute($column, Str::uuid7()->toString());
            }
        });
    }

    /**
     * Initialize the HasExternalId trait.
     *
     * Configures hidden attributes to hide the external ID column and
     * the numeric primary key from serialization output.
     *
     * @return void
     */
    protected function initializeHasExternalId(): void
    {
        if (!$this->usesExternalId) {
            return;
        }

        $this->hidden = array_unique([
            ...$this->hidden,
            $this->externalIdColumn,
            $this->primaryKey,
        ]);
    }

    /**
     * Determine if the model uses an external ID column.
     *
     * @return bool
     */
    public function usesExternalId(): bool
    {
        return $this->usesExternalId && $this->hasAttribute($this->externalIdColumn);
    }

    /**
     * Get the name of the external ID column.
     *
     * @return string
     */
    public function getExternalIdColumn(): string
    {
        return $this->externalIdColumn;
    }

    /**
     * Get the external ID value.
     *
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        if (!$this->usesExternalId()) {
            return null;
        }

        return $this->getAttribute($this->externalIdColumn);
    }

    /**
     * Find a model by its external ID.
     *
     * @param string $externalId The external ID to search for
     * @return static|null
     */
    public static function findByExternalId(string $externalId): ?static
    {
        $instance = new static();

        if (!$instance->usesExternalId()) {
            return null;
        }

        return static::query()
            ->where($instance->getExternalIdColumn(), $externalId)
            ->first();
    }

    /**
     * Find a model by its external ID or throw an exception.
     *
     * @param string $externalId The external ID to search for
     * @return static
     *
     * @throws ModelNotFoundException
     */
    public static function findByExternalIdOrFail(string $externalId): static
    {
        $instance = new static();

        if (!$instance->usesExternalId()) {
            throw new ModelNotFoundException(
                'External ID is not enabled for this model.'
            );
        }

        return static::query()
            ->where($instance->getExternalIdColumn(), $externalId)
            ->firstOrFail();
    }
}
