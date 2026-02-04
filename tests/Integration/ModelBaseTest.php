<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ExternalIdModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class ModelBaseTest extends IntegrationTestCase
{
    public function testFillConvertsAttributesToSnakeCase(): void
    {
        $model = new TestModel();
        $model->fill([
            'name' => 'Test',
            'isActive' => false,
            'birthDate' => '2000-01-01',
        ]);

        $this->assertSame('Test', $model->name);
        $this->assertFalse($model->is_active);
        $this->assertSame('2000-01-01', $model->birth_date);
    }

    public function testFillIgnoresNonExistentAttributes(): void
    {
        $model = new TestModel();
        $model->fill([
            'name' => 'Test',
            'nonExistent' => 'value',
        ]);

        $this->assertSame('Test', $model->name);
        $this->assertNull($model->getAttribute('non_existent'));
    }

    public function testFillWithEmptyArrayDoesNothing(): void
    {
        $model = new TestModel();
        $model->fill([]);

        $this->assertNull($model->name);
    }

    public function testToArrayReturnsAllVisibleAttributes(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test Title';
        $model->save();

        $array = $model->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertSame('Test Title', $array['title']);
    }

    public function testToArrayWithExternalIdReplacesIdField(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $array = $model->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertNotNull($model->getExternalId());
        $this->assertSame($model->getExternalId(), $array['id']);
    }

    public function testToSoftArrayReturnsOnlyPrimaryKey(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test';
        $model->save();

        $array = $model->toSoftArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertCount(1, $array);
        $this->assertSame($model->id, $array['id']);
    }

    public function testToSoftArrayWithExternalIdReturnsExternalId(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $array = $model->toSoftArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertCount(1, $array);
        $this->assertSame($model->getExternalId(), $array['id']);
    }

    public function testDefaultTimestampsAreEnabled(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test';
        $model->save();

        $this->assertNotNull($model->created_at);
        $this->assertNotNull($model->updated_at);
    }

    public function testSnakeAttributesIsFalse(): void
    {
        $this->assertFalse(SimpleModel::$snakeAttributes);
    }

    public function testDefaultPrimaryKeyIsId(): void
    {
        $model = new SimpleModel();

        $this->assertSame('id', $model->getKeyName());
    }

    public function testDefaultKeyTypeIsInt(): void
    {
        $model = new SimpleModel();

        $this->assertSame('int', $model->getKeyType());
    }

    public function testIncrementingIsTrue(): void
    {
        $model = new SimpleModel();

        $this->assertTrue($model->getIncrementing());
    }

    public function testSoftDeletesIsEnabled(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test';
        $model->save();

        $model->delete();

        $this->assertSoftDeleted('simple_models', ['id' => $model->id]);
        $this->assertNotNull($model->deleted_at);
    }
}
