<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Casts;

use DevToolbelt\LaravelEloquentPlus\Exceptions\ValidationException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Cast that converts external UUID to internal numeric ID for foreign keys.
 *
 * This cast allows the application to receive UUIDs from external sources
 * (like API requests) while storing numeric IDs in the database for performance.
 *
 * Usage in model:
 * ```php
 * protected $casts = [
 *     'product_id' => UuidToIdCast::class . ':products',
 *     'category_id' => UuidToIdCast::class . ':categories,id',
 *     'user_id' => UuidToIdCast::class . ':users,id,uuid',
 * ];
 * ```
 *
 * @package DevToolbelt\LaravelEloquentPlus\Casts
 */
final readonly class UuidToIdCast implements CastsAttributes
{
    /**
     * The default column name for external ID lookups.
     */
    private const string DEFAULT_EXTERNAL_ID_COLUMN = 'external_id';

    /**
     * @param string $relatedTableName The name of the related table to lookup
     * @param string|null $relatedPkName The primary key column name (defaults to the attribute key)
     * @param string $externalIdColumn The external ID column name for lookup
     */
    public function __construct(
        private string $relatedTableName,
        private ?string $relatedPkName = null,
        private string $externalIdColumn = self::DEFAULT_EXTERNAL_ID_COLUMN,
    ) {
    }

    /**
     * Transform the attribute from the underlying model values.
     *
     * Returns the numeric ID as stored in the database.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     * @return mixed
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * Converts UUID to numeric ID by looking up the related table.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     * @return mixed
     * @throws ValidationException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $record = $this->findRelatedRecord($value);
        $pkColumn = $this->relatedPkName ?? 'id';

        if (!$record) {
            $message = "The selected '{$key}' is invalid. Record not found in '{$this->relatedTableName}'.";

            throw new ValidationException(
                [[
                    'field' => $key,
                    'error' => 'relationRecordNotFound',
                    'value' => $value,
                    'table' => $this->relatedTableName,
                    'message' => $message
                ]],
                $message
            );
        }

        return $record->{$pkColumn};
    }

    /**
     * Find the related record by external ID.
     *
     * @param string $externalId The external UUID to search for
     * @return stdClass|null The found record or null
     */
    private function findRelatedRecord(string $externalId): ?stdClass
    {
        return DB::table($this->relatedTableName)
            ->where($this->externalIdColumn, $externalId)
            ->first();
    }
}
