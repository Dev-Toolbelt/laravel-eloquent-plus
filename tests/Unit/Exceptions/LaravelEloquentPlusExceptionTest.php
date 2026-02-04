<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Unit\Exceptions;

use DevToolbelt\LaravelEloquentPlus\Exceptions\LaravelEloquentPlusException;
use DevToolbelt\LaravelEloquentPlus\Tests\TestCase;
use Exception;

final class LaravelEloquentPlusExceptionTest extends TestCase
{
    public function testExtendsPhpException(): void
    {
        $exception = new LaravelEloquentPlusException();

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testCanSetMessage(): void
    {
        $exception = new LaravelEloquentPlusException('Test error message');

        $this->assertSame('Test error message', $exception->getMessage());
    }

    public function testCanSetCode(): void
    {
        $exception = new LaravelEloquentPlusException('Error', 500);

        $this->assertSame(500, $exception->getCode());
    }

    public function testCanSetPreviousException(): void
    {
        $previous = new Exception('Previous error');
        $exception = new LaravelEloquentPlusException('Error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCanBeCaughtAsBaseException(): void
    {
        $caught = false;

        try {
            throw new LaravelEloquentPlusException('Test');
        } catch (LaravelEloquentPlusException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }
}
