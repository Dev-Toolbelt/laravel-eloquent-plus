<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Exceptions;

/**
 * Exception thrown when a required model property is missing.
 *
 * @package DevToolbelt\LaravelEloquentPlus\Exceptions
 */
class MissingModelPropertyException extends LaravelEloquentPlusException
{
    /**
     * Create a new MissingModelPropertyException instance.
     *
     * @param string $modelClass The model class name
     * @param string $propertyName The missing property name
     */
    public function __construct(string $modelClass, string $propertyName)
    {
        $message = 'The property "%s" is required in model "%s". '
            . 'Please, add this column in your table and the property in the fillable attribute in your model.';

        parent::__construct(sprintf($message, $propertyName, $modelClass));
    }
}
