<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

/**
 * Trait for automatic hidden attributes setup.
 *
 * Automatically hides soft delete columns (deleted_at and deleted_by)
 * from array/JSON serialization output.
 *
 * @package DevToolbelt\LaravelEloquentPlus\Concerns
 */
trait HasHiddenAttributes
{
    /**
     * Initialize the HasHiddenAttributes trait.
     *
     * @return void
     */
    protected function initializeHasHiddenAttributes(): void
    {
        $this->setupHidden();
    }

    /**
     * Set up default hidden attributes for serialization.
     *
     * Automatically hides soft delete columns (deleted_at and deleted_by)
     * from array/JSON output. Custom hidden attributes defined in the model
     * are preserved and merged with these defaults.
     *
     * @return void
     */
    private function setupHidden(): void
    {
        $defaultHidden = [$this->primaryKey];

        if ($this->hasAttribute(static::DELETED_AT)) {
            $defaultHidden[] = static::DELETED_AT;
        }

        if ($this->hasAttribute(static::DELETED_BY)) {
            $defaultHidden[] = static::DELETED_BY;
        }

        if ($this->usesExternalId() && $this->hasAttribute($this->getExternalIdColumn())) {
            $defaultHidden[] = $this->getExternalIdColumn();
        }

        $this->hidden = array_unique([...$defaultHidden, ...$this->hidden]);
    }
}
