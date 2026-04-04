<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\ModelBase;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestUser;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class PartialColumnsTest extends IntegrationTestCase
{
    // -------------------------------------------------------
    // Partial Timestamps
    // -------------------------------------------------------

    public function testModelWithOnlyCreatedAtPopulatesCreatedAt(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_created_at_models';
            protected $fillable = ['title', 'created_at'];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertNotNull($model->created_at);
    }

    public function testModelWithOnlyCreatedAtDoesNotPopulateUpdatedAt(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_created_at_models';
            protected $fillable = ['title', 'created_at'];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertArrayNotHasKey('updated_at', $model->getAttributes());
    }

    public function testModelWithOnlyUpdatedAtPopulatesUpdatedAt(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_updated_at_models';
            protected $fillable = ['title', 'updated_at'];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertNotNull($model->updated_at);
    }

    public function testModelWithOnlyUpdatedAtDoesNotPopulateCreatedAt(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_updated_at_models';
            protected $fillable = ['title', 'updated_at'];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertArrayNotHasKey('created_at', $model->getAttributes());
    }

    public function testModelWithNoTimestampColumnsSkipsBothTimestamps(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'no_timestamps_models';
            protected $fillable = ['title'];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertArrayNotHasKey('created_at', $model->getAttributes());
        $this->assertArrayNotHasKey('updated_at', $model->getAttributes());
    }

    public function testModelWithTimestampsDisabledSkipsAutoPopulate(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_created_at_models';
            protected $fillable = ['title', 'created_at'];
            public $timestamps = false;
        };

        $model->title = 'Test';

        $reflection = new \ReflectionMethod($model, 'autoPopulateFields');
        $reflection->setAccessible(true);
        $reflection->invoke($model);

        // Even though created_at is declared, $timestamps = false prevents auto-population
        $this->assertNull($model->getAttribute('created_at'));
    }

    public function testUpdateOnModelWithOnlyUpdatedAtSetsUpdatedAt(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_updated_at_models';
            protected $fillable = ['title', 'updated_at'];
        };

        $model->title = 'Test';
        $model->save();

        $originalUpdatedAt = $model->updated_at;

        $model->title = 'Updated';
        $model->save();

        $row = DB::table('only_updated_at_models')->first();
        $this->assertNotNull($row->updated_at);
        $this->assertSame('Updated', $row->title);
    }

    // -------------------------------------------------------
    // Partial Blamable
    // -------------------------------------------------------

    public function testModelWithOnlyCreatedByPopulatesCreatedBy(): void
    {
        $user = $this->createAndLoginUser();

        $model = new class extends ModelBase {
            protected $table = 'partial_blamable_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'created_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertSame($user->id, $model->getAttribute('created_by'));
    }

    public function testModelWithOnlyCreatedByDoesNotPopulateUpdatedBy(): void
    {
        $this->createAndLoginUser();

        $model = new class extends ModelBase {
            protected $table = 'partial_blamable_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'created_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertArrayNotHasKey('updated_by', $model->getAttributes());
    }

    public function testModelWithOnlyCreatedBySkipsUpdatedByOnUpdate(): void
    {
        $this->createAndLoginUser();

        $model = new class extends ModelBase {
            protected $table = 'partial_blamable_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'created_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
        };

        $model->title = 'Test';
        $model->save();

        $model->title = 'Updated';
        $model->save();

        $this->assertArrayNotHasKey('updated_by', $model->getAttributes());
    }

    public function testModelWithOnlyDeletedByPopulatesDeletedByOnSoftDelete(): void
    {
        $user = $this->createAndLoginUser();

        $model = new class extends ModelBase {
            protected $table = 'only_deleted_by_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'deleted_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
        };

        $model->title = 'Test';
        $model->save();

        $model->delete();

        $this->assertSame($user->id, $model->getAttribute('deleted_by'));
    }

    public function testModelWithOnlyDeletedBySkipsCreatedByAndUpdatedBy(): void
    {
        $this->createAndLoginUser();

        $model = new class extends ModelBase {
            protected $table = 'only_deleted_by_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'deleted_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertArrayNotHasKey('created_by', $model->getAttributes());
        $this->assertArrayNotHasKey('updated_by', $model->getAttributes());
    }

    public function testBlamableWithoutColumnsSkipsAllBlamableOnCreate(): void
    {
        $this->createAndLoginUser();

        $model = new class extends ModelBase {
            protected $table = 'no_timestamps_models';
            protected bool $usesBlamable = true;
            protected $fillable = ['title'];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertArrayNotHasKey('created_by', $model->getAttributes());
        $this->assertArrayNotHasKey('updated_by', $model->getAttributes());
        $this->assertArrayNotHasKey('deleted_by', $model->getAttributes());
    }

    // -------------------------------------------------------
    // runSoftDelete with partial columns
    // -------------------------------------------------------

    public function testSoftDeleteSkipsDeletedAtWhenColumnNotDeclared(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'no_deleted_at_models';
            protected $fillable = ['title', 'created_at', 'updated_at'];
        };

        $model->title = 'Test';
        $model->save();

        // Without deleted_at in fillable, soft delete should not set it
        $model->delete();

        $row = DB::table('no_deleted_at_models')->where('id', $model->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('Test', $row->title);
    }

    public function testSoftDeleteSetsUpdatedAtWhenDeclared(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_deleted_by_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'deleted_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
        };

        $model->title = 'Test';
        $model->save();

        $updatedAtBefore = $model->updated_at;

        $model->delete();

        $this->assertNotNull($model->deleted_at);
        $this->assertNotNull($model->updated_at);
    }

    public function testSoftDeleteSkipsUpdatedAtInRunSoftDeleteWhenNotDeclared(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_deleted_by_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'deleted_by',
                'created_at',
                'deleted_at',
            ];
        };

        $this->createAndLoginUser();

        $model->title = 'Test';
        $model->save();

        $model->delete();

        // updated_at is not declared, so runSoftDelete should skip it
        $this->assertArrayNotHasKey('updated_at', $model->getAttributes());
        $this->assertNotNull($model->deleted_at);
    }

    // -------------------------------------------------------
    // autoPopulateFields with partial columns
    // -------------------------------------------------------

    public function testAutoPopulateSkipsCreatedAtWhenNotDeclared(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_updated_at_models';
            protected $fillable = ['title', 'updated_at'];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertNotNull($model->updated_at);
        $this->assertArrayNotHasKey('created_at', $model->getAttributes());
    }

    public function testAutoPopulateSkipsUpdatedAtWhenNotDeclared(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_created_at_models';
            protected $fillable = ['title', 'created_at'];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertNotNull($model->created_at);
        $this->assertArrayNotHasKey('updated_at', $model->getAttributes());
    }

    public function testAutoPopulateSkipsBlamableCreatedByWhenNotDeclared(): void
    {
        $this->createAndLoginUser();

        $model = new class extends ModelBase {
            protected $table = 'only_deleted_by_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'deleted_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertArrayNotHasKey('created_by', $model->getAttributes());
    }

    public function testAutoPopulateSkipsBlamableUpdatedByWhenNotDeclared(): void
    {
        $this->createAndLoginUser();

        $model = new class extends ModelBase {
            protected $table = 'partial_blamable_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'created_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
        };

        $model->title = 'Test';
        $model->save();

        $this->assertArrayNotHasKey('updated_by', $model->getAttributes());
    }

    // -------------------------------------------------------
    // setupRules with partial columns
    // -------------------------------------------------------

    public function testSetupRulesSkipsTimestampRulesWhenNotDeclared(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'no_timestamps_models';
            protected $fillable = ['title'];
        };

        $rules = $model->getRules();

        $this->assertArrayNotHasKey('created_at', $rules);
        $this->assertArrayNotHasKey('updated_at', $rules);
        $this->assertArrayNotHasKey('deleted_at', $rules);
    }

    public function testSetupRulesAddsOnlyDeclaredTimestampRules(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_created_at_models';
            protected $fillable = ['title', 'created_at'];
        };

        $rules = $model->getRules();

        $this->assertArrayHasKey('created_at', $rules);
        $this->assertArrayNotHasKey('updated_at', $rules);
    }

    public function testSetupRulesSkipsBlamableRulesWhenNotDeclared(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'partial_blamable_models';
            protected bool $usesBlamable = true;
            protected $fillable = [
                'title',
                'created_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
        };

        $rules = $model->getRules();

        $this->assertArrayHasKey('created_by', $rules);
        $this->assertArrayNotHasKey('updated_by', $rules);
        $this->assertArrayNotHasKey('deleted_by', $rules);
    }

    // -------------------------------------------------------
    // getCreatedAtColumn / getUpdatedAtColumn with partial columns
    // -------------------------------------------------------

    public function testGetCreatedAtColumnReturnsNullWhenNotDeclared(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_updated_at_models';
            protected $fillable = ['title', 'updated_at'];
        };

        $this->assertNull($model->getCreatedAtColumn());
        $this->assertSame('updated_at', $model->getUpdatedAtColumn());
    }

    public function testGetUpdatedAtColumnReturnsNullWhenNotDeclared(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'only_created_at_models';
            protected $fillable = ['title', 'created_at'];
        };

        $this->assertSame('created_at', $model->getCreatedAtColumn());
        $this->assertNull($model->getUpdatedAtColumn());
    }

    // -------------------------------------------------------
    // Full save flow (create + update) without timestamps
    // -------------------------------------------------------

    public function testCreateAndUpdateModelWithoutTimestampColumns(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'no_timestamps_with_fields';
            protected $fillable = ['name', 'price', 'quantity'];
            protected array $rules = [
                'name' => ['required', 'string'],
                'price' => ['required', 'numeric'],
                'quantity' => ['required', 'integer'],
            ];
        };

        $model->name = 'Test';
        $model->price = 10.50;
        $model->quantity = 100;
        $model->save();

        $this->assertNotNull($model->id);
        $this->assertArrayNotHasKey('created_at', $model->getAttributes());
        $this->assertArrayNotHasKey('updated_at', $model->getAttributes());

        // Update should not try to set updated_at
        $model->price = 7;
        $model->quantity = 1428;
        $model->save();

        $row = DB::table('no_timestamps_with_fields')
            ->where('id', $model->id)
            ->first();

        $this->assertEquals(7, $row->price);
        $this->assertEquals(1428, $row->quantity);
    }

    public function testUpdateModelLoadedFromDbWithoutTimestampColumns(): void
    {
        DB::table('no_timestamps_with_fields')->insert([
            'name' => 'Test',
            'price' => 10.50,
            'quantity' => 100,
        ]);

        $modelClass = new class extends ModelBase {
            protected $table = 'no_timestamps_with_fields';
            protected $fillable = ['name', 'price', 'quantity'];
            protected array $rules = [
                'name' => ['required', 'string'],
                'price' => ['required', 'numeric'],
                'quantity' => ['required', 'integer'],
            ];
        };

        // Load from DB (simulates find())
        $model = $modelClass::query()->first();

        $model->price = 7;
        $model->quantity = 1428;
        $model->save();

        $row = DB::table('no_timestamps_with_fields')
            ->where('id', $model->id)
            ->first();

        $this->assertEquals(7, $row->price);
        $this->assertEquals(1428, $row->quantity);
    }

    public function testHasAttributeReturnsFalseForUndeclaredTimestamps(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'no_timestamps_with_fields';
            protected $fillable = ['name', 'price', 'quantity'];
            protected array $rules = [
                'name' => ['required', 'string'],
                'price' => ['required', 'numeric'],
                'quantity' => ['required', 'integer'],
            ];
        };

        $this->assertFalse($model->hasAttribute('created_at'));
        $this->assertFalse($model->hasAttribute('updated_at'));
        $this->assertFalse($model->hasAttribute('deleted_at'));
        $this->assertTrue($model->hasAttribute('name'));
        $this->assertTrue($model->hasAttribute('price'));
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function createAndLoginUser(): TestUser
    {
        $user = new TestUser();
        $user->name = 'Test User';
        $user->email = 'partial-columns-' . uniqid() . '@example.com';
        $user->save();

        Auth::login($user);

        return $user;
    }
}
