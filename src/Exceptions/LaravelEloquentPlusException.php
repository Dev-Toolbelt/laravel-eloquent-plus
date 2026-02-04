<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Exceptions;

use Exception;

/**
 * Base exception class for Laravel Eloquent Plus package.
 *
 * All package-specific exceptions should extend this class
 * to allow catching all package exceptions with a single catch block.
 *
 * @package DevToolbelt\LaravelEloquentPlus\Exceptions
 */
class LaravelEloquentPlusException extends Exception
{
}
