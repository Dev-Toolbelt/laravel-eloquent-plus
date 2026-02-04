<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration\Concerns;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\CastTestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\DateTimeFormatModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\FakeEnumRule;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\StringRulesModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestStatus;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Validation\Rules\Enum;

final class HasAutoCastingFullCoverageTest extends IntegrationTestCase
{
    public function testSetupCastsSkipsStringRules(): void
    {
        $model = new StringRulesModel();

        // String rules should be skipped, no casts added
        $casts = $model->getCasts();

        // Only default Laravel casts should exist
        $this->assertArrayNotHasKey('title', $casts);
    }

    public function testSetupCastsBooleanRule(): void
    {
        $model = new TestModel();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertSame('boolean', $casts['is_active']);
    }

    public function testSetupCastsIntegerRule(): void
    {
        $model = new TestModel();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('quantity', $casts);
        $this->assertSame('integer', $casts['quantity']);
    }

    public function testSetupCastsNumericRuleToFloat(): void
    {
        $model = new TestModel();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('price', $casts);
        $this->assertSame('float', $casts['price']);
    }

    public function testSetupCastsArrayRule(): void
    {
        $model = new TestModel();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('metadata', $casts);
        $this->assertSame('array', $casts['metadata']);
    }

    public function testSetupCastsDateFormatRuleToDate(): void
    {
        $model = new CastTestModel();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('birth_date', $casts);
        $this->assertSame('date', $casts['birth_date']);
    }

    public function testSetupCastsDateFormatWithTimeToDatetime(): void
    {
        $model = new DateTimeFormatModel();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('published_at', $casts);
        $this->assertSame('datetime', $casts['published_at']);
    }

    public function testSetupCastsDateRuleToDatetime(): void
    {
        $model = new CastTestModel();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('published_at', $casts);
        $this->assertSame('datetime', $casts['published_at']);
    }

    public function testSetupCastsEnumRule(): void
    {
        $model = new TestModel();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('status', $casts);
        $this->assertSame(TestStatus::class, $casts['status']);
    }

    public function testExtractEnumCastReturnsEnumClass(): void
    {
        $model = new TestModel();
        $enumRule = new Enum(TestStatus::class);

        $reflection = new \ReflectionMethod($model, 'extractEnumCast');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($model, $enumRule);

        $this->assertSame(TestStatus::class, $result);
    }

    public function testExtractEnumCastReturnsNullForInvalidEnum(): void
    {
        $model = new TestModel();

        // Create a fake Enum rule where the type property is not a valid enum
        $fakeEnumRule = new FakeEnumRule();

        $reflection = new \ReflectionMethod($model, 'extractEnumCast');
        $reflection->setAccessible(true);

        // This will return null because there's no enum_exists() match
        $result = $reflection->invoke($model, $fakeEnumRule);

        $this->assertNull($result);
    }

    public function testCustomCastsOverrideAutoCasts(): void
    {
        $model = new CastTestModel();
        $casts = $model->getCasts();

        // phone has a custom cast defined in the model
        $this->assertArrayHasKey('phone', $casts);
        $this->assertStringContainsString('OnlyNumbers', $casts['phone']);
    }

    public function testMultipleCastsAppliedCorrectly(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->is_active = '1';
        $model->quantity = '100';
        $model->price = '99.99';
        $model->metadata = ['key' => 'value'];
        $model->save();

        $model->refresh();

        $this->assertIsBool($model->is_active);
        $this->assertTrue($model->is_active);

        $this->assertIsInt($model->quantity);
        $this->assertSame(100, $model->quantity);

        $this->assertIsFloat($model->price);
        $this->assertSame(99.99, $model->price);

        $this->assertIsArray($model->metadata);
        $this->assertSame(['key' => 'value'], $model->metadata);
    }

    public function testEnumCastWorksWithStringValue(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->status = 'published';
        $model->save();

        $model->refresh();

        $this->assertInstanceOf(TestStatus::class, $model->status);
        $this->assertSame(TestStatus::PUBLISHED, $model->status);
    }

    public function testEnumCastWorksWithEnumValue(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->status = TestStatus::DRAFT;
        $model->save();

        $model->refresh();

        $this->assertInstanceOf(TestStatus::class, $model->status);
        $this->assertSame(TestStatus::DRAFT, $model->status);
    }
}
