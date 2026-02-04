<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\CastTestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestStatus;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class HasAutoCastingTest extends IntegrationTestCase
{
    public function testBooleanCastFromValidationRules(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->is_active = '1';
        $model->save();

        $model->refresh();

        $this->assertIsBool($model->is_active);
        $this->assertTrue($model->is_active);
    }

    public function testIntegerCastFromValidationRules(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->quantity = '100';
        $model->save();

        $model->refresh();

        $this->assertIsInt($model->quantity);
        $this->assertSame(100, $model->quantity);
    }

    public function testNumericCastToFloat(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->price = '99.99';
        $model->save();

        $model->refresh();

        $this->assertIsFloat($model->price);
        $this->assertSame(99.99, $model->price);
    }

    public function testDateCastFromDateFormat(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->birth_date = '1990-05-15';
        $model->save();

        $model->refresh();

        // Date is formatted as string by default (not Carbon)
        $this->assertSame('1990-05-15', $model->birth_date);
    }

    public function testDatetimeCastFromDateRule(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->published_at = '2024-01-15 10:30:00';
        $model->save();

        $model->refresh();

        $this->assertNotNull($model->published_at);
    }

    public function testArrayCastFromValidationRules(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = ['key' => 'value', 'nested' => ['data' => true]];
        $model->save();

        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertSame('value', $model->metadata['key']);
        $this->assertTrue($model->metadata['nested']['data']);
    }

    public function testEnumCastFromValidationRules(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->status = TestStatus::PUBLISHED;
        $model->save();

        $model->refresh();

        $this->assertInstanceOf(TestStatus::class, $model->status);
        $this->assertSame(TestStatus::PUBLISHED, $model->status);
    }

    public function testEnumCastFromStringValue(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->status = 'draft';
        $model->save();

        $model->refresh();

        $this->assertInstanceOf(TestStatus::class, $model->status);
        $this->assertSame(TestStatus::DRAFT, $model->status);
    }

    public function testCustomCastOverridesAutoCast(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->phone = '(11) 99999-9999';
        $model->save();

        $model->refresh();

        $this->assertSame('11999999999', $model->phone);
    }

    public function testMultipleCastsApplied(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->is_active = '0';
        $model->quantity = '50';
        $model->price = '199.99';
        $model->metadata = ['setting' => true];
        $model->save();

        $model->refresh();

        $this->assertIsBool($model->is_active);
        $this->assertFalse($model->is_active);
        $this->assertIsInt($model->quantity);
        $this->assertSame(50, $model->quantity);
        $this->assertIsFloat($model->price);
        $this->assertSame(199.99, $model->price);
        $this->assertIsArray($model->metadata);
    }

    public function testCastsArrayContainsInferredCasts(): void
    {
        $model = new TestModel();

        $casts = $model->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertSame('boolean', $casts['is_active']);

        $this->assertArrayHasKey('quantity', $casts);
        $this->assertSame('integer', $casts['quantity']);

        $this->assertArrayHasKey('price', $casts);
        $this->assertSame('float', $casts['price']);

        $this->assertArrayHasKey('metadata', $casts);
        $this->assertSame('array', $casts['metadata']);
    }
}
