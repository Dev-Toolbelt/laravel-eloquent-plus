<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration\Concerns;

use DevToolbelt\LaravelEloquentPlus\Exceptions\ValidationException;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ExternalIdWithBlamableModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\FullAttributesModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ValidatedModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class HasValidationFullCoverageTest extends IntegrationTestCase
{
    public function testSetupRulesAddsDefaultRulesWhenAttributesExist(): void
    {
        $model = new FullAttributesModel();
        $rules = $model->getRules();

        // Check that default rules are added for existing attributes
        $this->assertArrayHasKey('id', $rules);
        $this->assertContains('nullable', $rules['id']);
        $this->assertContains('integer', $rules['id']);

        $this->assertArrayHasKey('created_at', $rules);
        $this->assertContains('nullable', $rules['created_at']);

        $this->assertArrayHasKey('updated_at', $rules);
        $this->assertContains('nullable', $rules['updated_at']);

        $this->assertArrayHasKey('deleted_at', $rules);
        $this->assertContains('nullable', $rules['deleted_at']);
    }

    public function testSetupRulesAddsExternalIdRulesWhenEnabled(): void
    {
        $model = new ExternalIdWithBlamableModel();
        $rules = $model->getRules();

        $this->assertArrayHasKey('external_id', $rules);
        $this->assertContains('required', $rules['external_id']);
        $this->assertContains('uuid', $rules['external_id']);
        $this->assertContains('string', $rules['external_id']);
        $this->assertContains('size:36', $rules['external_id']);
    }

    public function testSetupRulesAddsBlamableRulesWithExternalId(): void
    {
        $model = new ExternalIdWithBlamableModel();
        $rules = $model->getRules();

        // With external ID enabled, blamable rules should use UUID validation
        $this->assertArrayHasKey('created_by', $rules);
        $this->assertContains('nullable', $rules['created_by']);
        $this->assertContains('uuid', $rules['created_by']);

        $this->assertArrayHasKey('updated_by', $rules);
        $this->assertContains('nullable', $rules['updated_by']);

        $this->assertArrayHasKey('deleted_by', $rules);
        $this->assertContains('nullable', $rules['deleted_by']);
    }

    public function testSetupRulesAddsBlamableRulesWithoutExternalId(): void
    {
        $model = new FullAttributesModel();
        $rules = $model->getRules();

        // Without external ID, blamable rules should use integer validation
        $this->assertArrayHasKey('created_by', $rules);
        $this->assertContains('nullable', $rules['created_by']);
        $this->assertContains('integer', $rules['created_by']);

        $this->assertArrayHasKey('updated_by', $rules);
        $this->assertContains('nullable', $rules['updated_by']);
        $this->assertContains('integer', $rules['updated_by']);

        $this->assertArrayHasKey('deleted_by', $rules);
        $this->assertContains('nullable', $rules['deleted_by']);
        $this->assertContains('integer', $rules['deleted_by']);
    }

    public function testGetUsersTableReturnsConfiguredUserModelTable(): void
    {
        $model = new FullAttributesModel();

        $reflection = new \ReflectionMethod($model, 'getUsersTable');
        $reflection->setAccessible(true);

        $table = $reflection->invoke($model);

        // The TestUser model is configured in IntegrationTestCase
        $this->assertSame('users', $table);
    }

    public function testGetUsersTableReturnsFallbackWhenModelNotConfigured(): void
    {
        // Temporarily clear the auth config
        config(['auth.providers.users.model' => null]);

        $model = new FullAttributesModel();

        $reflection = new \ReflectionMethod($model, 'getUsersTable');
        $reflection->setAccessible(true);

        $table = $reflection->invoke($model);

        $this->assertSame('users', $table);
    }

    public function testGetUsersTableReturnsFallbackWhenModelClassNotExists(): void
    {
        // Set a non-existent class
        config(['auth.providers.users.model' => 'NonExistent\\User\\Class']);

        $model = new FullAttributesModel();

        $reflection = new \ReflectionMethod($model, 'getUsersTable');
        $reflection->setAccessible(true);

        $table = $reflection->invoke($model);

        $this->assertSame('users', $table);
    }

    public function testValidationCallbackWithEmptyRulesDoesNotValidate(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test';

        // Should not throw - empty rules
        $model->save();

        $this->assertDatabaseHas('simple_models', ['title' => 'Test']);
    }

    public function testValidationCallbackPassesWhenValid(): void
    {
        $model = new ValidatedModel();
        $model->title = 'Valid Title';
        $model->save();

        $this->assertDatabaseHas('simple_models', ['title' => 'Valid Title']);
    }

    public function testValidationCallbackThrowsOnInvalidCreate(): void
    {
        $this->expectException(ValidationException::class);

        $model = new ValidatedModel();
        $model->save();
    }

    public function testValidationCallbackThrowsOnInvalidUpdate(): void
    {
        $model = new ValidatedModel();
        $model->title = 'Valid';
        $model->save();

        $this->expectException(ValidationException::class);

        $model->title = null;
        $model->save();
    }

    public function testValidationExceptionContainsDetailedErrors(): void
    {
        try {
            $model = new ValidatedModel();
            $model->save();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            $this->assertNotEmpty($errors);
            $this->assertArrayHasKey('field', $errors[0]);
            $this->assertArrayHasKey('error', $errors[0]);
            $this->assertArrayHasKey('value', $errors[0]);
            $this->assertArrayHasKey('message', $errors[0]);

            $this->assertSame('title', $errors[0]['field']);
            $this->assertSame('required', $errors[0]['error']);
        }
    }
}
