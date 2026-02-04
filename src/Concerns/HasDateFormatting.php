<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Concerns;

use Illuminate\Support\Carbon;

/**
 * Trait for date/datetime formatting and output control.
 *
 * Provides functionality to control how date fields are returned:
 * - As formatted strings (default)
 * - As Carbon instances (when $carbonInstanceInFieldDates is true)
 *
 * @package DevToolbelt\LaravelEloquentPlus\Concerns
 */
trait HasDateFormatting
{
    /**
     * The date formats for each date/datetime attribute.
     *
     * Maps attribute names to their output format string.
     * Used by getAttribute() to return formatted strings instead of Carbon instances.
     *
     * @var array<string, string>
     */
    protected array $dateFormats = [];

    /**
     * Indicates if date fields should return Carbon instances instead of formatted strings.
     *
     * When false (default), date/datetime fields return formatted strings.
     * When true, date/datetime fields return Carbon instances.
     *
     * @var bool
     */
    protected bool $carbonInstanceInFieldDates = false;

    /**
     * Resolve the appropriate date cast based on validation rules.
     *
     * Analyzes date-related validation rules to determine the correct cast:
     * - 'date_format:Y-m-d' (date only) -> 'date' cast with Y-m-d format
     * - 'date_format' with time component -> 'datetime' cast with specified format
     * - 'date' (generic) -> 'datetime' cast with $dateFormat
     *
     * Also stores the output format in $dateFormats for use by getAttribute().
     *
     * @param string $attribute The attribute name
     * @param array<int, mixed> $rules The validation rules for the attribute
     * @return string|null The cast type ('date' or 'datetime'), or null if not a date field
     */
    protected function resolveDateCast(string $attribute, array $rules): ?string
    {
        $dateOnlyFormat = $this->getDateOnlyFormat();

        foreach ($rules as $rule) {
            if (!is_string($rule)) {
                continue;
            }

            if (str_starts_with($rule, 'date_format:')) {
                $format = substr($rule, strlen('date_format:'));
                $this->dateFormats[$attribute] = $format;

                if ($format === $dateOnlyFormat) {
                    return 'date';
                }

                return 'datetime';
            }
        }

        if (in_array('date', $rules, true)) {
            $this->dateFormats[$attribute] = $this->dateFormat;
            return 'datetime';
        }

        return null;
    }

    /**
     * Get the date-only format extracted from $dateFormat.
     *
     * Extracts the date portion (before space) from the full datetime format.
     * Falls back to the full format if no space is found.
     *
     * @return string The date-only format string
     */
    protected function getDateOnlyFormat(): string
    {
        if (str_contains($this->dateFormat, ' ')) {
            return explode(' ', $this->dateFormat)[0];
        }

        return $this->dateFormat;
    }

    /**
     * Get the date formats mapping.
     *
     * @return array<string, string>
     */
    public function getDateFormats(): array
    {
        return $this->dateFormats;
    }
}
