<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration\Concerns;

use DevToolbelt\LaravelEloquentPlus\Exceptions\ValidationException;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ExternalIdWithBlamableModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\FullAttributesModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestUser;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ValidatedModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Auth;

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

        // created_at is required (auto-populated in beforeValidate)
        $this->assertArrayHasKey('created_at', $rules);
        $this->assertContains('required', $rules['created_at']);

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

        // Blamable rules always use integer validation for foreign keys
        // All blamable fields are nullable to allow saving without authenticated user
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

    public function testSetupRulesAddsBlamableRulesWithoutExternalId(): void
    {
        $model = new FullAttributesModel();
        $rules = $model->getRules();

        // Without external ID, blamable rules should use integer validation
        // All blamable fields are nullable to allow saving without authenticated user
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

    public function testBeforeValidateAutoPopulatesCreatedAt(): void
    {
        $model = new TestModel();
        $model->name = 'Test';

        // Before save, created_at should be null
        $this->assertNull($model->getAttribute('created_at'));

        $model->save();

        // After save, created_at should be auto-populated
        $this->assertNotNull($model->getAttribute('created_at'));
    }

    public function testBeforeValidateAutoPopulatesCreatedBy(): void
    {
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'beforevalidate@example.com';
        $user->save();

        Auth::login($user);

        $model = new TestModel();
        $model->name = 'Test';

        // Before save, created_by should be null
        $this->assertNull($model->getAttribute('created_by'));

        $model->save();

        // After save, created_by should be auto-populated with user ID
        $this->assertSame($user->id, $model->getAttribute('created_by'));
    }

    public function testBeforeValidateAutoPopulatesUpdatedAt(): void
    {
        $model = new TestModel();
        $model->name = 'Test';

        // Before save, updated_at should be null
        $this->assertNull($model->getAttribute('updated_at'));

        $model->save();

        // After save, updated_at should be auto-populated
        $this->assertNotNull($model->getAttribute('updated_at'));
    }

    public function testBeforeValidateAutoPopulatesUpdatedBy(): void
    {
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'updatedby@example.com';
        $user->save();

        Auth::login($user);

        $model = new TestModel();
        $model->name = 'Test';

        // Before save, updated_by should be null
        $this->assertNull($model->getAttribute('updated_by'));

        $model->save();

        // After save, updated_by should be auto-populated with user ID
        $this->assertSame($user->id, $model->getAttribute('updated_by'));
    }

    public function testBeforeValidateDoesNotOverwriteExistingCreatedAt(): void
    {
        $customDate = now()->subDays(10);

        $model = new TestModel();
        $model->name = 'Test';
        $model->created_at = $customDate;
        $model->save();

        // The custom date should be preserved
        $this->assertSame(
            $customDate->format('Y-m-d H:i:s'),
            $model->created_at->format('Y-m-d H:i:s')
        );
    }

    public function testBeforeValidateDoesNotOverwriteExistingCreatedBy(): void
    {
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'nooverwrite@example.com';
        $user->save();

        // Create another user to use as existing created_by
        $existingUser = new TestUser();
        $existingUser->name = 'Existing User';
        $existingUser->email = 'existingcreator@example.com';
        $existingUser->save();

        Auth::login($user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->created_by = $existingUser->id;
        $model->save();

        // The existing user ID should be preserved
        $this->assertSame($existingUser->id, $model->created_by);
    }

    public function testCreatedByIsNullableWhenUserNotAuthenticated(): void
    {
        // Ensure no user is logged in
        Auth::logout();
        Auth::forgetGuards();

        // FullAttributesModel has created_by in its pre-populated attributes,
        // but created_by is nullable so it should save without errors
        $model = new FullAttributesModel();
        $model->name = 'Test';
        $model->save();

        // created_by should be null since no user was authenticated
        $this->assertNull($model->created_by);
    }

    public function testBeforeValidateDoesNotPopulateCreatedByOnExistingModel(): void
    {
        // First create the model without a user
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        // created_by should be null since no user was logged in
        $this->assertNull($model->created_by);

        // Now login a user
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'existingmodel@example.com';
        $user->save();

        Auth::login($user);

        // Update the model
        $model->name = 'Updated';
        $model->save();

        // created_by should still be null because model already exists (!$this->exists is false)
        $this->assertNull($model->created_by);

        // But updated_by should be set
        $this->assertSame($user->id, $model->updated_by);
    }

    public function testBeforeValidateDoesNotOverwriteExistingUpdatedBy(): void
    {
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'nooverwriteupdatedby@example.com';
        $user->save();

        // Create another user to use as existing updated_by
        $existingUser = new TestUser();
        $existingUser->name = 'Existing User';
        $existingUser->email = 'existingupdater@example.com';
        $existingUser->save();

        Auth::login($user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->updated_by = $existingUser->id;
        $model->save();

        // The existing user ID should be preserved
        $this->assertSame($existingUser->id, $model->updated_by);
    }

    public function testBeforeValidateUpdatedByOnUpdateWhenNull(): void
    {
        // Create model without user
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertNull($model->updated_by);

        // Login user
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'updatedbynull@example.com';
        $user->save();

        Auth::login($user);

        // Clear updated_by and update
        $model->updated_by = null;
        $model->name = 'Updated';
        $model->save();

        // updated_by should now be populated
        $this->assertSame($user->id, $model->updated_by);
    }

    public function testBeforeValidateCreatedByNotPopulatedWhenNoUserAndColumnIsNull(): void
    {
        // Ensure no user is logged in
        Auth::logout();
        Auth::forgetGuards();

        // TestModel has created_by column but no required rule
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        // created_by should remain null since no user is authenticated
        $this->assertNull($model->created_by);
    }

    public function testBeforeValidateUpdatedByNotPopulatedWhenNoUser(): void
    {
        // Ensure no user is logged in
        Auth::logout();
        Auth::forgetGuards();

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        // updated_by should remain null since no user is authenticated
        $this->assertNull($model->updated_by);
    }

    public function testBeforeValidateUpdatedByPopulatedOnUpdateEvenWhenModelExists(): void
    {
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'updatedbyonupdate@example.com';
        $user->save();

        // Create model without user
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        Auth::login($user);

        // Update the existing model
        $model->name = 'Updated Name';
        $model->updated_by = null; // Reset to test auto-population
        $model->save();

        // updated_by should be populated even on existing model
        $this->assertSame($user->id, $model->updated_by);
    }

    public function testAutoPopulateFieldsDirectlyPopulatesCreatedBy(): void
    {
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'directcreatedby@example.com';
        $user->save();

        Auth::login($user);

        // FullAttributesModel has blamable columns and doesn't override beforeValidate()
        $model = new FullAttributesModel();
        $model->name = 'Test';

        // Ensure created_by is null
        $this->assertNull($model->getAttribute('created_by'));

        // Call autoPopulateFields directly via reflection to test the actual implementation
        $reflection = new \ReflectionMethod($model, 'autoPopulateFields');
        $reflection->setAccessible(true);
        $reflection->invoke($model);

        // created_by should be set by autoPopulateFields
        $this->assertSame($user->id, $model->getAttribute('created_by'));
    }

    public function testAutoPopulateFieldsDirectlyPopulatesUpdatedBy(): void
    {
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'directupdatedby@example.com';
        $user->save();

        Auth::login($user);

        // FullAttributesModel has blamable columns and doesn't override beforeValidate()
        $model = new FullAttributesModel();
        $model->name = 'Test';

        // Ensure updated_by is null
        $this->assertNull($model->getAttribute('updated_by'));

        // Call autoPopulateFields directly via reflection to test the actual implementation
        $reflection = new \ReflectionMethod($model, 'autoPopulateFields');
        $reflection->setAccessible(true);
        $reflection->invoke($model);

        // updated_by should be set by autoPopulateFields
        $this->assertSame($user->id, $model->getAttribute('updated_by'));
    }
}
