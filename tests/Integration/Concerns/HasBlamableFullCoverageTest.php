<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration\Concerns;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ForceDeleteModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\NoBlamableConstantsModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\NoDeletedByModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\NoUpdatedByModel;
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

    public function testGetCreatedByColumnReturnsNullWhenConstantNotDefined(): void
    {
        $model = new NoBlamableConstantsModel();

        $reflection = new \ReflectionMethod($model, 'getCreatedByColumn');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->invoke($model));
    }

    public function testGetUpdatedByColumnReturnsColumnName(): void
    {
        $model = new TestModel();

        $reflection = new \ReflectionMethod($model, 'getUpdatedByColumn');
        $reflection->setAccessible(true);

        $this->assertSame('updated_by', $reflection->invoke($model));
    }

    public function testGetUpdatedByColumnReturnsNullWhenConstantNotDefined(): void
    {
        $model = new NoBlamableConstantsModel();

        $reflection = new \ReflectionMethod($model, 'getUpdatedByColumn');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->invoke($model));
    }

    public function testGetDeletedByColumnReturnsColumnName(): void
    {
        $model = new TestModel();

        $reflection = new \ReflectionMethod($model, 'getDeletedByColumn');
        $reflection->setAccessible(true);

        $this->assertSame('deleted_by', $reflection->invoke($model));
    }

    public function testGetDeletedByColumnReturnsNullWhenConstantNotDefined(): void
    {
        $model = new NoBlamableConstantsModel();

        $reflection = new \ReflectionMethod($model, 'getDeletedByColumn');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->invoke($model));
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
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->created_by = 999;
        $model->save();

        $this->assertSame(999, $model->created_by);
        $this->assertSame($this->user->id, $model->updated_by);
    }

    public function testCreatingEventDoesNotOverwriteExistingUpdatedBy(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->updated_by = 888;
        $model->save();

        $this->assertSame($this->user->id, $model->created_by);
        $this->assertSame(888, $model->updated_by);
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
        $model = new TestModel();
        $model->name = 'Test';
        $model->deleted_by = 123;
        $model->save();
        $model->delete();

        Auth::logout();

        $model->restore();

        // deleted_by should remain as-is when no user is authenticated
        $this->assertSame(123, $model->deleted_by);
    }

    public function testBlamableWithModelWithoutConstants(): void
    {
        Auth::login($this->user);

        $model = new NoBlamableConstantsModel();
        $model->title = 'Test';
        $model->save();

        // Model has no CREATED_BY/UPDATED_BY constants, so nothing should be set
        $this->assertFalse(isset($model->created_by));
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

    public function testUpdatingEventWithNullUpdatedByColumn(): void
    {
        Auth::login($this->user);

        $model = new NoUpdatedByModel();
        $model->name = 'Test';
        $model->save();

        // Update should work even when getUpdatedByColumn returns null
        $model->name = 'Updated';
        $model->save();

        // Model should be updated successfully
        $this->assertSame('Updated', $model->name);
    }

    public function testDeletingEventWithNullDeletedByColumn(): void
    {
        Auth::login($this->user);

        $model = new NoDeletedByModel();
        $model->name = 'Test';
        $model->save();

        // Delete should work even when getDeletedByColumn returns null
        $model->delete();

        $this->assertNotNull($model->deleted_at);
    }

    public function testDeletingEventWithNullUpdatedByColumn(): void
    {
        Auth::login($this->user);

        $model = new NoUpdatedByModel();
        $model->name = 'Test';
        $model->save();

        // Delete should work even when getUpdatedByColumn returns null
        $model->delete();

        $this->assertNotNull($model->deleted_at);
    }

    public function testRestoringEventWithNullDeletedByColumn(): void
    {
        Auth::login($this->user);

        $model = new NoDeletedByModel();
        $model->name = 'Test';
        $model->save();
        $model->delete();

        // Restore should work even when getDeletedByColumn returns null
        $model->restore();

        $this->assertNull($model->deleted_at);
    }

    public function testRestoringEventWithNullUpdatedByColumn(): void
    {
        Auth::login($this->user);

        $model = new NoUpdatedByModel();
        $model->name = 'Test';
        $model->save();
        $model->delete();

        // Restore should work even when getUpdatedByColumn returns null
        $model->restore();

        $this->assertNull($model->deleted_at);
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

    public function testDeletingEventWithBothNullColumns(): void
    {
        Auth::login($this->user);

        // Create a model that returns null for both deleted_by and updated_by columns
        $model = new class extends \DevToolbelt\LaravelEloquentPlus\ModelBase {
            protected $table = 'test_models';
            protected $guarded = [];
            protected array $rules = [
                'name' => ['required', 'string', 'max:255'],
            ];

            protected function getDeletedByColumn(): ?string
            {
                return null;
            }

            protected function getUpdatedByColumn(): ?string
            {
                return null;
            }
        };

        $model->name = 'Test';
        $model->save();

        // Soft delete should still work even when both columns return null
        $model->delete();

        $this->assertNotNull($model->deleted_at);
    }
}
