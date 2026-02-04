<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\CastTestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class HasDateFormattingTest extends IntegrationTestCase
{
    public function testDateFieldReturnsFormattedString(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->birth_date = '1990-05-15';
        $model->save();

        $model->refresh();

        $this->assertSame('1990-05-15', $model->birth_date);
    }

    public function testDateFormatsAreStoredForDateFields(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->birth_date = '1990-05-15';
        $model->save();

        $dateFormats = $model->getDateFormats();

        $this->assertArrayHasKey('birth_date', $dateFormats);
    }

    public function testCarbonInstanceWhenEnabled(): void
    {
        $model = new class extends CastTestModel {
            protected bool $carbonInstanceInFieldDates = true;
        };

        $model->name = 'Test';
        $model->birth_date = '1990-05-15';
        $model->save();

        $model->refresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->birth_date);
    }

    public function testDateOnlyFormatExtraction(): void
    {
        $model = new SimpleModel();

        $reflection = new \ReflectionMethod($model, 'getDateOnlyFormat');
        $reflection->setAccessible(true);

        $dateOnlyFormat = $reflection->invoke($model);

        $this->assertSame('Y-m-d', $dateOnlyFormat);
    }

    public function testDateFormatWithoutSpace(): void
    {
        $model = new class extends SimpleModel {
            public $dateFormat = 'Y-m-d';
        };

        $reflection = new \ReflectionMethod($model, 'getDateOnlyFormat');
        $reflection->setAccessible(true);

        $dateOnlyFormat = $reflection->invoke($model);

        $this->assertSame('Y-m-d', $dateOnlyFormat);
    }
}
