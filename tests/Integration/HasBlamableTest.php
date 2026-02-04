<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestUser;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Auth;

final class HasBlamableTest extends IntegrationTestCase
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

    public function testCreatedByIsSetOnCreate(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertSame($this->user->id, $model->created_by);
    }

    public function testUpdatedByIsSetOnCreate(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertSame($this->user->id, $model->updated_by);
    }

    public function testUpdatedByIsSetOnUpdate(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        Auth::login($this->user);

        $model->name = 'Updated';
        $model->save();

        $this->assertSame($this->user->id, $model->updated_by);
    }

    public function testDeletedByIsSetOnSoftDelete(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $model->delete();

        $this->assertSame($this->user->id, $model->deleted_by);
    }

    public function testDeletedByIsClearedOnRestore(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $model->delete();
        $this->assertSame($this->user->id, $model->deleted_by);

        $model->restore();
        $this->assertNull($model->deleted_by);
    }

    public function testUpdatedByIsSetOnRestore(): void
    {
        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $model->delete();

        $newUser = new TestUser();
        $newUser->name = 'New User';
        $newUser->email = 'new@example.com';
        $newUser->save();

        Auth::login($newUser);

        $model->restore();

        $this->assertSame($newUser->id, $model->updated_by);
    }

    public function testBlamableColumnsNotSetWhenNoUserAuthenticated(): void
    {
        Auth::logout();

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertNull($model->created_by);
        $this->assertNull($model->updated_by);
    }

    public function testExistingCreatedByIsNotOverwritten(): void
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
    }

    public function testBlamableWorksWithDifferentUsers(): void
    {
        $user2 = new TestUser();
        $user2->name = 'User 2';
        $user2->email = 'user2@example.com';
        $user2->save();

        Auth::login($this->user);

        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertSame($this->user->id, $model->created_by);

        Auth::login($user2);

        $model->name = 'Updated';
        $model->save();

        $this->assertSame($this->user->id, $model->created_by);
        $this->assertSame($user2->id, $model->updated_by);
    }
}
