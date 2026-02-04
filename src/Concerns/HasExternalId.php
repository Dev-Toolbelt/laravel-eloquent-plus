<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

use DevToolbelt\LaravelEloquentPlus\Exceptions\ExternalIdNotEnabledException;
use DevToolbelt\LaravelEloquentPlus\Exceptions\MissingModelPropertyException;
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
     * @throws MissingModelPropertyException
     */
    protected static function bootHasExternalId(): void
    {
        static::creating(static function (self $model): void {
            if (!$model->usesExternalId()) {
                return;
            }

            if (!$model->hasAttribute($model->getExternalIdColumn())) {
                throw new MissingModelPropertyException($model::class, $model->getExternalIdColumn());
            }

            if ($model->getAttribute($model->getExternalIdColumn())) {
                return;
            }

            $model->setAttribute($model->getExternalIdColumn(), Str::uuid7()->toString());
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

        $this->fillable = array_unique([
            ...$this->fillable,
            $this->externalIdColumn
        ]);
    }

    /**
     * Determine if the model uses an external ID column.
     *
     * @return bool
     */
    public function usesExternalId(): bool
    {
        return $this->usesExternalId;
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
     * @throws ExternalIdNotEnabledException
     */
    public static function findByExternalId(string $externalId): ?static
    {
        /** @phpstan-ignore new.static */
        $instance = new static();

        if (!$instance->usesExternalId()) {
            throw new ExternalIdNotEnabledException();
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
     * @throws ExternalIdNotEnabledException
     */
    public static function findByExternalIdOrFail(string $externalId): static
    {
        /** @phpstan-ignore new.static */
        $instance = new static();

        if (!$instance->usesExternalId()) {
            throw new ExternalIdNotEnabledException();
        }

        return static::query()
            ->where($instance->getExternalIdColumn(), $externalId)
            ->firstOrFail();
    }
}
