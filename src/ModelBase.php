<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus;

use DevToolbelt\LaravelEloquentPlus\Concerns\HasAutoCasting;
use DevToolbelt\LaravelEloquentPlus\Concerns\HasBlamable;
use DevToolbelt\LaravelEloquentPlus\Concerns\HasCastAliases;
use DevToolbelt\LaravelEloquentPlus\Concerns\HasDateFormatting;
use DevToolbelt\LaravelEloquentPlus\Concerns\HasExternalId;
use DevToolbelt\LaravelEloquentPlus\Concerns\HasHiddenAttributes;
use DevToolbelt\LaravelEloquentPlus\Concerns\HasLifecycleHooks;
use DevToolbelt\LaravelEloquentPlus\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Abstract base model class that extends Laravel's Eloquent Model.
 *
 * Provides additional functionality including:
 * - Automatic validation based on defined rules (HasValidation)
 * - Automatic type casting inferred from validation rules (HasAutoCasting)
 * - Date formatting control with string/Carbon output (HasDateFormatting)
 * - Lifecycle hooks for custom logic (HasLifecycleHooks)
 * - Automatic hidden attributes for soft deletes (HasHiddenAttributes)
 * - Built-in soft deletes with user tracking (HasBlamable)
 * - Optional external ID (UUID) support (HasExternalId)
 * - Automatic snake_case attribute conversion on fill
 *
 * @package DevToolbelt\LaravelEloquentPlus
 *
 * @phpstan-consistent-constructor
 */
abstract class ModelBase extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasBlamable;
    use HasCastAliases;
    use HasExternalId;
    use HasValidation;
    use HasDateFormatting;
    use HasAutoCasting;
    use HasLifecycleHooks;
    use HasHiddenAttributes;

    /**
     * The name of the "created at" column.
     */
    public const string CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     */
    public const string UPDATED_AT = 'updated_at';

    /**
     * The name of the "deleted at" column.
     */
    public const string DELETED_AT = 'deleted_at';

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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    public $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * Indicates whether attributes are snake cased on arrays.
     *
     * @var bool
     */
    public static $snakeAttributes = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        /** @phpstan-ignore new.static */
        $model = new static();

        if ($model->hasAttribute($model->getDeletedAtColumn())) {
            static::addGlobalScope(new SoftDeletingScope());
        }
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftDeletes()
    {
    }

    /**
     * Fill the model with an array of attributes.
     *
     * Extends the default fill behavior to automatically convert
     * camelCase attribute names to snake_case before filling.
     *
     * @param array<string, mixed> $attributes Key-value pairs of attributes to fill
     * @return static
     */
    public function fill(array $attributes): self
    {
        if (empty($attributes)) {
            return parent::fill($attributes);
        }

        foreach ($attributes as $attributeName => $value) {
            $snakeCaseAttr = Str::snake($attributeName);

            if (!$this->hasAttribute($snakeCaseAttr)) {
                continue;
            }

            $attributes[$snakeCaseAttr] = $value;
        }

        return parent::fill($attributes);
    }

    /**
     * Get an attribute from the model.
     *
     * Overrides the default behavior to return formatted date strings
     * instead of Carbon instances for date/datetime fields.
     * This behavior can be disabled by setting $carbonInstanceInFieldDates to true.
     *
     * @param string $key The attribute name
     * @return mixed The attribute value (string for dates when $carbonInstanceInFieldDates is false)
     */
    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        if ($value instanceof Carbon && isset($this->dateFormats[$key])) {
            if ($this->carbonInstanceInFieldDates) {
                return $value;
            }

            return $value->format($this->dateFormats[$key]);
        }

        return $value;
    }

    /**
     * Convert the model instance to an array.
     *
     * When external ID is enabled, exposes 'id' with the external ID value
     * instead of the numeric primary key for public-facing output.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        if ($this->usesExternalId()) {
            $data[$this->primaryKey] = $this->getExternalId();
        }

        return $data;
    }

    /**
     * Convert the model to a minimal array representation.
     *
     * Returns only the primary key field (or external ID if enabled),
     * useful for references or lightweight serialization.
     *
     * @return array<string, mixed> Array containing only the identifier
     */
    public function toSoftArray(): array
    {
        if ($this->usesExternalId()) {
            return [$this->primaryKey => $this->getExternalId()];
        }

        return $this->returnOnlyFields([$this->primaryKey]);
    }

    /**
     * Filter the model's array representation to include only specified fields.
     *
     * @param string[] $fieldsToReturn List of field names to include in the output
     * @return array<string, mixed> Filtered array containing only the specified fields
     */
    protected function returnOnlyFields(array $fieldsToReturn): array
    {
        return array_filter($this->toArray(), function ($key) use ($fieldsToReturn) {
            return in_array($key, $fieldsToReturn);
        }, ARRAY_FILTER_USE_KEY);
    }
}
