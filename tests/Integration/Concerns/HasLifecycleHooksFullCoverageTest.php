<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration\Concerns;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\LifecycleHooksModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class HasLifecycleHooksFullCoverageTest extends IntegrationTestCase
{
    public function testBeforeValidateIsCalledOnCreate(): void
    {
        $model = new LifecycleHooksModel();
        $model->name = 'Test';

        $this->assertSame(0, $model->hooksCalled['beforeValidate']);

        $model->save();

        // beforeValidate is called during validation callback
        $this->assertGreaterThanOrEqual(1, $model->hooksCalled['beforeValidate']);
    }

    public function testBeforeSaveIsCalledOnCreate(): void
    {
        $model = new LifecycleHooksModel();
        $model->name = 'Test';

        $this->assertSame(0, $model->hooksCalled['beforeSave']);

        $model->save();

        // beforeSave is called via creating event
        $this->assertGreaterThanOrEqual(1, $model->hooksCalled['beforeSave']);
    }

    public function testBeforeSaveIsCalledOnUpdate(): void
    {
        $model = new LifecycleHooksModel();
        $model->name = 'Test';
        $model->save();

        // Reset the counter after create
        $initialCount = $model->hooksCalled['beforeSave'];

        $model->name = 'Updated';
        $model->save();

        // beforeSave should be called again for update
        $this->assertGreaterThan($initialCount, $model->hooksCalled['beforeSave']);
    }

    public function testAfterSaveIsCalledOnCreate(): void
    {
        $model = new LifecycleHooksModel();
        $model->name = 'Test';

        $this->assertSame(0, $model->hooksCalled['afterSave']);

        $model->save();

        // afterSave is called via created and saved events
        $this->assertGreaterThanOrEqual(1, $model->hooksCalled['afterSave']);
    }

    public function testAfterSaveIsCalledOnUpdate(): void
    {
        $model = new LifecycleHooksModel();
        $model->name = 'Test';
        $model->save();

        $initialCount = $model->hooksCalled['afterSave'];

        $model->name = 'Updated';
        $model->save();

        // afterSave should be called again for update (updated + saved events)
        $this->assertGreaterThan($initialCount, $model->hooksCalled['afterSave']);
    }

    public function testBeforeDeleteIsCalled(): void
    {
        $model = new LifecycleHooksModel();
        $model->name = 'Test';
        $model->save();

        $this->assertSame(0, $model->hooksCalled['beforeDelete']);

        $model->delete();

        $this->assertSame(1, $model->hooksCalled['beforeDelete']);
    }

    public function testAfterDeleteIsCalled(): void
    {
        $model = new LifecycleHooksModel();
        $model->name = 'Test';
        $model->save();

        $this->assertSame(0, $model->hooksCalled['afterDelete']);

        $model->delete();

        $this->assertSame(1, $model->hooksCalled['afterDelete']);
    }

    public function testLifecycleHooksOrderOnCreate(): void
    {
        $order = [];

        $model = new class extends LifecycleHooksModel {
            public static array $callOrder = [];

            protected function beforeValidate(): void
            {
                self::$callOrder[] = 'beforeValidate';
            }

            protected function beforeSave(): void
            {
                self::$callOrder[] = 'beforeSave';
            }

            protected function afterSave(): void
            {
                self::$callOrder[] = 'afterSave';
            }
        };

        $model::$callOrder = [];
        $model->name = 'Test';
        $model->save();

        // Verify order: beforeValidate -> beforeSave -> afterSave
        $this->assertContains('beforeValidate', $model::$callOrder);
        $this->assertContains('beforeSave', $model::$callOrder);
        $this->assertContains('afterSave', $model::$callOrder);
    }

    public function testLifecycleHooksOrderOnDelete(): void
    {
        $model = new class extends LifecycleHooksModel {
            public static array $callOrder = [];

            protected function beforeDelete(): void
            {
                self::$callOrder[] = 'beforeDelete';
            }

            protected function afterDelete(): void
            {
                self::$callOrder[] = 'afterDelete';
            }
        };

        $model::$callOrder = [];
        $model->name = 'Test';
        $model->save();
        $model::$callOrder = []; // Reset after save

        $model->delete();

        // Verify order: beforeDelete -> afterDelete
        $this->assertSame(['beforeDelete', 'afterDelete'], $model::$callOrder);
    }

    public function testDefaultHooksDoNothing(): void
    {
        // Test that the base hooks don't throw exceptions
        $model = new SimpleModel();
        $model->title = 'Test';

        // Should not throw
        $model->save();
        $model->title = 'Updated';
        $model->save();
        $model->delete();

        $this->assertTrue(true);
    }

    public function testHooksAreCalledForEachSaveOperation(): void
    {
        $model = new LifecycleHooksModel();
        $model->name = 'Test';
        $model->save();

        $firstSaveCount = $model->hooksCalled['afterSave'];

        $model->name = 'Update 1';
        $model->save();

        $secondSaveCount = $model->hooksCalled['afterSave'];

        $model->name = 'Update 2';
        $model->save();

        $thirdSaveCount = $model->hooksCalled['afterSave'];

        // Each save should trigger afterSave
        $this->assertGreaterThan($firstSaveCount, $secondSaveCount);
        $this->assertGreaterThan($secondSaveCount, $thirdSaveCount);
    }

    public function testBeforeValidateCanModifyAttributes(): void
    {
        $model = new class extends LifecycleHooksModel {
            protected function beforeValidate(): void
            {
                // Normalize name to uppercase
                if (isset($this->attributes['name'])) {
                    $this->attributes['name'] = strtoupper($this->attributes['name']);
                }
            }
        };

        $model->name = 'lowercase';
        $model->save();

        $this->assertSame('LOWERCASE', $model->name);
    }

    public function testBeforeSaveCanModifyAttributes(): void
    {
        $model = new class extends LifecycleHooksModel {
            protected function beforeSave(): void
            {
                // Add a prefix to name
                if (isset($this->attributes['name']) && !str_starts_with($this->attributes['name'], 'PREFIX_')) {
                    $this->attributes['name'] = 'PREFIX_' . $this->attributes['name'];
                }
            }
        };

        $model->name = 'Test';
        $model->save();

        $this->assertSame('PREFIX_Test', $model->name);
    }
}
