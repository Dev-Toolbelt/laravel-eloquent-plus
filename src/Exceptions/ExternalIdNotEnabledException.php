<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Exceptions;

/**
 * @package DevToolbelt\LaravelEloquentPlus\Exceptions
 */
class ExternalIdNotEnabledException extends LaravelEloquentPlusException
{
    /**
     * Create a new ExternalIdNotEnabledException instance.
     */
    public function __construct()
    {
        $message = "External ID is not enabled for this model. "
            . "To enable it, declare the 'usesExternalId' property with the value true.";

        parent::__construct($message);
    }
}
