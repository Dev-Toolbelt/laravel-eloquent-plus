<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Exceptions\ValidationException;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ValidatedModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class HasValidationTest extends IntegrationTestCase
{
    public function testValidationPassesWithValidData(): void
    {
        $model = new ValidatedModel();
        $model->title = 'Valid Title';

        $this->expectNotToPerformAssertions();
        $model->save();
    }

    public function testValidationFailsOnCreate(): void
    {
        $this->expectException(ValidationException::class);

        $model = new ValidatedModel();
        $model->save();
    }

    public function testValidationFailsOnUpdate(): void
    {
        $model = new ValidatedModel();
        $model->title = 'Initial Title';
        $model->save();

        $this->expectException(ValidationException::class);

        $model->title = null;
        $model->save();
    }

    public function testValidationExceptionContainsFieldInfo(): void
    {
        try {
            $model = new ValidatedModel();
            $model->save();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasErrorFor('title'));
            $this->assertContains('title', $e->getFailedFields());
            $this->assertNotEmpty($e->getErrors());
        }
    }

    public function testCustomValidationRulesAreApplied(): void
    {
        try {
            $model = new TestModel();
            $model->name = 'Test';
            $model->email = 'invalid-email';
            $model->save();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasErrorFor('email'));
        }
    }

    public function testGetRulesReturnsCustomRules(): void
    {
        $model = new TestModel();
        $rules = $model->getRules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
    }

    public function testRulesContainExpectedValidation(): void
    {
        $model = new TestModel();
        $rules = $model->getRules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
    }

    public function testRulesForNullableFields(): void
    {
        $model = new TestModel();
        $rules = $model->getRules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertContains('nullable', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    public function testMultipleValidationErrors(): void
    {
        try {
            $model = new TestModel();
            $model->email = 'invalid';
            $model->save();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertTrue($e->hasErrorFor('name'));
            $failedFields = $e->getFailedFields();
            $this->assertContains('name', $failedFields);
        }
    }

    public function testValidationWithNullableFieldsPasses(): void
    {
        $model = new TestModel();
        $model->name = 'Test Name';
        $model->email = null;
        $model->phone = null;
        $model->quantity = null;

        $model->save();

        $this->assertDatabaseHas('test_models', [
            'id' => $model->id,
            'name' => 'Test Name',
        ]);
    }
}
