<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Unit\Exceptions;

use DevToolbelt\LaravelEloquentPlus\Exceptions\LaravelEloquentPlusException;
use DevToolbelt\LaravelEloquentPlus\Exceptions\ValidationException;
use DevToolbelt\LaravelEloquentPlus\Tests\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function testExtendsBaseException(): void
    {
        $exception = new ValidationException([]);

        $this->assertInstanceOf(LaravelEloquentPlusException::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new ValidationException([]);

        $this->assertSame('The given data was invalid.', $exception->getMessage());
    }

    public function testCustomMessage(): void
    {
        $exception = new ValidationException([], 'Custom validation error');

        $this->assertSame('Custom validation error', $exception->getMessage());
    }

    public function testGetErrors(): void
    {
        $errors = [
            [
                'field' => 'name',
                'error' => 'required',
                'value' => null,
                'message' => 'The name field is required.',
            ],
            [
                'field' => 'email',
                'error' => 'email',
                'value' => 'invalid',
                'message' => 'The email must be valid.',
            ],
        ];

        $exception = new ValidationException($errors);

        $this->assertSame($errors, $exception->getErrors());
    }

    public function testGetErrorsByField(): void
    {
        $errors = [
            ['field' => 'name', 'error' => 'required', 'value' => null, 'message' => 'Name is required.'],
            ['field' => 'name', 'error' => 'string', 'value' => null, 'message' => 'Name must be a string.'],
            ['field' => 'email', 'error' => 'email', 'value' => 'invalid', 'message' => 'Invalid email.'],
        ];

        $exception = new ValidationException($errors);
        $grouped = $exception->getErrorsByField();

        $this->assertArrayHasKey('name', $grouped);
        $this->assertArrayHasKey('email', $grouped);
        $this->assertCount(2, $grouped['name']);
        $this->assertCount(1, $grouped['email']);

        $this->assertSame('required', $grouped['name'][0]['error']);
        $this->assertSame('string', $grouped['name'][1]['error']);
        $this->assertSame('email', $grouped['email'][0]['error']);
    }

    public function testGetMessages(): void
    {
        $errors = [
            ['field' => 'name', 'error' => 'required', 'value' => null, 'message' => 'Name is required.'],
            ['field' => 'name', 'error' => 'string', 'value' => null, 'message' => 'Name must be a string.'],
            ['field' => 'email', 'error' => 'email', 'value' => 'invalid', 'message' => null],
        ];

        $exception = new ValidationException($errors);
        $messages = $exception->getMessages();

        $this->assertArrayHasKey('name', $messages);
        $this->assertCount(2, $messages['name']);
        $this->assertSame('Name is required.', $messages['name'][0]);
        $this->assertSame('Name must be a string.', $messages['name'][1]);
        $this->assertArrayNotHasKey('email', $messages);
    }

    public function testGetFirstMessageFor(): void
    {
        $errors = [
            ['field' => 'name', 'error' => 'required', 'value' => null, 'message' => 'Name is required.'],
            ['field' => 'name', 'error' => 'string', 'value' => null, 'message' => 'Name must be a string.'],
            ['field' => 'email', 'error' => 'email', 'value' => 'invalid', 'message' => 'Invalid email.'],
        ];

        $exception = new ValidationException($errors);

        $this->assertSame('Name is required.', $exception->getFirstMessageFor('name'));
        $this->assertSame('Invalid email.', $exception->getFirstMessageFor('email'));
        $this->assertNull($exception->getFirstMessageFor('nonexistent'));
    }

    public function testGetFirstMessageForWithNullMessage(): void
    {
        $errors = [
            ['field' => 'name', 'error' => 'required', 'value' => null, 'message' => null],
            ['field' => 'name', 'error' => 'string', 'value' => null, 'message' => 'Name must be a string.'],
        ];

        $exception = new ValidationException($errors);

        $this->assertSame('Name must be a string.', $exception->getFirstMessageFor('name'));
    }

    public function testHasErrorFor(): void
    {
        $errors = [
            ['field' => 'name', 'error' => 'required', 'value' => null, 'message' => 'Name is required.'],
            ['field' => 'email', 'error' => 'email', 'value' => 'invalid', 'message' => 'Invalid email.'],
        ];

        $exception = new ValidationException($errors);

        $this->assertTrue($exception->hasErrorFor('name'));
        $this->assertTrue($exception->hasErrorFor('email'));
        $this->assertFalse($exception->hasErrorFor('password'));
    }

    public function testGetFailedFields(): void
    {
        $errors = [
            ['field' => 'name', 'error' => 'required', 'value' => null, 'message' => 'Name is required.'],
            ['field' => 'name', 'error' => 'string', 'value' => null, 'message' => 'Name must be a string.'],
            ['field' => 'email', 'error' => 'email', 'value' => 'invalid', 'message' => 'Invalid email.'],
        ];

        $exception = new ValidationException($errors);
        $failedFields = $exception->getFailedFields();

        $this->assertCount(2, $failedFields);
        $this->assertContains('name', $failedFields);
        $this->assertContains('email', $failedFields);
    }

    public function testEmptyErrors(): void
    {
        $exception = new ValidationException([]);

        $this->assertSame([], $exception->getErrors());
        $this->assertSame([], $exception->getErrorsByField());
        $this->assertSame([], $exception->getMessages());
        $this->assertSame([], $exception->getFailedFields());
        $this->assertNull($exception->getFirstMessageFor('any'));
        $this->assertFalse($exception->hasErrorFor('any'));
    }
}
