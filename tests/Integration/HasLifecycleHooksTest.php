<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class HasLifecycleHooksTest extends IntegrationTestCase
{
    public function testBeforeValidateIsCalledOnCreate(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertTrue($model->beforeValidateCalled);
    }

    public function testBeforeValidateIsCalledOnUpdate(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $model->beforeValidateCalled = false;

        $model->name = 'Updated';
        $model->save();

        $this->assertTrue($model->beforeValidateCalled);
    }

    public function testBeforeSaveIsCalledOnCreate(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertTrue($model->beforeSaveCalled);
    }

    public function testBeforeSaveIsCalledOnUpdate(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $model->beforeSaveCalled = false;

        $model->name = 'Updated';
        $model->save();

        $this->assertTrue($model->beforeSaveCalled);
    }

    public function testAfterSaveIsCalledOnCreate(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $this->assertTrue($model->afterSaveCalled);
    }

    public function testAfterSaveIsCalledOnUpdate(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $model->afterSaveCalled = false;

        $model->name = 'Updated';
        $model->save();

        $this->assertTrue($model->afterSaveCalled);
    }

    public function testBeforeDeleteIsCalled(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $model->delete();

        $this->assertTrue($model->beforeDeleteCalled);
    }

    public function testAfterDeleteIsCalled(): void
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->save();

        $model->delete();

        $this->assertTrue($model->afterDeleteCalled);
    }

    public function testHooksAreCalledInCorrectOrder(): void
    {
        $order = [];

        $model = new class extends TestModel {
            /** @var array<int, string> */
            public array $callOrder = [];

            protected function beforeValidate(): void
            {
                $this->callOrder[] = 'beforeValidate';
            }

            protected function beforeSave(): void
            {
                $this->callOrder[] = 'beforeSave';
            }

            protected function afterSave(): void
            {
                $this->callOrder[] = 'afterSave';
            }
        };

        $model->name = 'Test';
        $model->save();

        $this->assertSame(['beforeValidate', 'beforeSave', 'afterSave', 'afterSave'], $model->callOrder);
    }
}
