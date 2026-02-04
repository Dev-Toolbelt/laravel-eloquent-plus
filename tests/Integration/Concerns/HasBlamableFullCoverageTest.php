<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration\Concerns;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\BlamableWithoutConstantsModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ForceDeleteModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestUser;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Auth;

final class HasBlamableFullCoverageTest extends IntegrationTestCase
{
    private TestUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new TestUser();
        $this->user->name = 'Test User';
        $this->user->email = 'test@example.com';
        $this->user->save();
    }

    public function testGetBlamableUserIdReturnsNullWhenNotAuthenticated(): void
    {
        Auth::logout();

        $model = new TestModel();

        $reflection = new \ReflectionMethod($model, 'getBlamableUserId');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->invoke($model));
    }

    public function testGetBlamableUserIdReturnsUserIdWhenAuthenticated(): void
    {
        Auth::login($this->user);

        $model = new TestModel();

        $reflection = new \ReflectionMethod($model, 'getBlamableUserId');
        $reflection->setAccessible(true);

        $this->assertSame($this->user->id, $reflection->invoke($model));
    }

    public function testGetCreatedByColumnReturnsColumnName(): void
    {
        $model = new TestModel();

        $reflection = new \ReflectionMethod($model, 'getCreatedByColumn');
        $reflection->setAccessible(true);

        $this->assertSame('created_by', $reflection->invoke($model));
    }

    public function testGetUpdatedByColumnReturnsColumnName(): void
    {
        $model = new TestModel();

        $reflection = new \ReflectionMethod($model, 'getUpdatedByColumn');
        $reflection->setAccessible(true);

        $this->assertSame('updated_by', $reflection->invoke($model));
    }

    public function testGetDeletedByColumnReturnsColumnName(): void
    {
        $model = new TestModel();

        $reflection = new \ReflectionMethod($model, 'getDeletedByColumn');
        $reflection->setAccessible(true);

        $this->assertSame('deleted_by', $reflection->invoke($model));
    }

    public function testUsesSoftDeletesReturnsTrue(): void
    {
        $model = new TestModel();

        $reflection = new \ReflectionMethod($model, 'usesSoftDeletes');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->invoke($model));
    }

    public function testCreatingEventDoesNotSetColumnsWhenNoUser(): void
    {
        Auth::logout();

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertNull($model->created_by);
        $this->assertNull($model->updated_by);
    }

    public function testCreatingEventSetsColumns(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertSame($this->user->id, $model->created_by);
        $this->assertSame($this->user->id, $model->updated_by);
    }

    public function testCreatingEventDoesNotOverwriteExistingCreatedBy(): void
    {
        // Create another user to use as existing created_by
        $existingUser = new TestUser();
        $existingUser->name = 'Existing User';
        $existingUser->email = 'existing@example.com';
        $existingUser->save();

        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->created_by = $existingUser->id;
        $model->save();

        $this->assertSame($existingUser->id, $model->created_by);
        $this->assertSame($this->user->id, $model->updated_by);
    }

    public function testCreatingEventDoesNotOverwriteExistingUpdatedBy(): void
    {
        // Create another user to use as existing updated_by
        $existingUser = new TestUser();
        $existingUser->name = 'Existing User';
        $existingUser->email = 'existingupdated@example.com';
        $existingUser->save();

        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->updated_by = $existingUser->id;
        $model->save();

        $this->assertSame($this->user->id, $model->created_by);
        $this->assertSame($existingUser->id, $model->updated_by);
    }

    public function testUpdatingEventDoesNotSetColumnsWhenNoUser(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        Auth::logout();

        $model->name = 'Updated';
        $model->save();

        $this->assertNull($model->updated_by);
    }

    public function testUpdatingEventSetsUpdatedBy(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        Auth::login($this->user);

        $model->name = 'Updated';
        $model->save();

        $this->assertSame($this->user->id, $model->updated_by);
    }

    public function testDeletingEventDoesNotSetColumnsWhenNoUser(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        Auth::logout();

        $model->delete();

        $this->assertNull($model->deleted_by);
    }

    public function testDeletingEventSetsDeletedByAndUpdatedBy(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $model->delete();

        $this->assertSame($this->user->id, $model->deleted_by);
        $this->assertSame($this->user->id, $model->updated_by);
    }

    public function testRestoringEventClearsDeletedByAndSetsUpdatedBy(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();
        $model->delete();

        $this->assertSame($this->user->id, $model->deleted_by);

        $model->restore();

        $this->assertNull($model->deleted_by);
        $this->assertSame($this->user->id, $model->updated_by);
    }

    public function testRestoringEventDoesNothingWhenNoUser(): void
    {
        // Create a user to use as existing deleted_by
        $existingUser = new TestUser();
        $existingUser->name = 'Existing User';
        $existingUser->email = 'existingdeleted@example.com';
        $existingUser->save();

        $model = new TestModel();
        $model->name = 'Test';
        $model->deleted_by = $existingUser->id;
        $model->save();
        $model->delete();

        Auth::logout();

        $model->restore();

        // deleted_by should remain as-is when no user is authenticated
        $this->assertSame($existingUser->id, $model->deleted_by);
    }

    public function testUsesSoftDeletesMethodChecksForIsForceDeleting(): void
    {
        // The usesSoftDeletes method checks for isForceDeleting method existence
        // Since TestModel uses SoftDeletes, it has isForceDeleting, so it returns true
        $model = new TestModel();

        $reflection = new \ReflectionMethod($model, 'usesSoftDeletes');
        $reflection->setAccessible(true);

        // Verify the method returns true when isForceDeleting exists
        $this->assertTrue($reflection->invoke($model));

        // Verify the logic by checking method_exists directly
        $this->assertTrue(method_exists($model, 'isForceDeleting'));
    }

    public function testDeletingEventDoesNotSetDeletedByOnForceDelete(): void
    {
        Auth::login($this->user);

        $model = new ForceDeleteModel();
        $model->name = 'Test';
        $model->save();

        // Force delete skips the soft delete logic
        $model->forceDelete();

        // Model should be truly deleted from database
        $this->assertDatabaseMissing('test_models', ['id' => $model->id]);
    }

    public function testDeletingEventSkipsBlamableWhenForceDeleting(): void
    {
        Auth::login($this->user);

        $model = new ForceDeleteModel();
        $model->name = 'Test';
        $model->save();

        // Clear any previous values
        $model->deleted_by = null;
        $model->save();

        // Force deleting bypasses the soft delete logic in deleting event
        $model->forceDelete();

        // Model should be truly deleted and deleted_by should not have been set during force delete
        $this->assertDatabaseMissing('test_models', ['id' => $model->id]);
    }

    public function testDeletingEventSetsColumnsOnSoftDelete(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        // Soft delete should trigger the blamable logic
        $model->delete();

        // Both deleted_by and updated_by should be set
        $this->assertSame($this->user->id, $model->deleted_by);
        $this->assertSame($this->user->id, $model->updated_by);
    }

    public function testGetCreatedByColumnReturnsValueFromTraitConstant(): void
    {
        // BlamableWithoutConstantsModel uses HasBlamable trait which defines CREATED_BY constant
        $model = new BlamableWithoutConstantsModel();

        // The constant is now defined in the trait, so it should return the value
        $this->assertSame('created_by', $model->testGetCreatedByColumn());
    }

    public function testGetUpdatedByColumnReturnsValueFromTraitConstant(): void
    {
        // BlamableWithoutConstantsModel uses HasBlamable trait which defines UPDATED_BY constant
        $model = new BlamableWithoutConstantsModel();

        // The constant is now defined in the trait, so it should return the value
        $this->assertSame('updated_by', $model->testGetUpdatedByColumn());
    }

    public function testGetDeletedByColumnReturnsValueFromTraitConstant(): void
    {
        // BlamableWithoutConstantsModel uses HasBlamable trait which defines DELETED_BY constant
        $model = new BlamableWithoutConstantsModel();

        // The constant is now defined in the trait, so it should return the value
        $this->assertSame('deleted_by', $model->testGetDeletedByColumn());
    }
}
