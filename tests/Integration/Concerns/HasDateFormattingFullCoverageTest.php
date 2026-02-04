<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration\Concerns;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\CastTestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\DateTimeFormatModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Support\Carbon;

final class HasDateFormattingFullCoverageTest extends IntegrationTestCase
{
    public function testResolveDateCastWithDateOnlyFormat(): void
    {
        $model = new CastTestModel();

        $reflection = new \ReflectionMethod($model, 'resolveDateCast');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($model, 'birth_date', ['nullable', 'date_format:Y-m-d']);

        $this->assertSame('date', $result);
    }

    public function testResolveDateCastWithDateTimeFormat(): void
    {
        $model = new DateTimeFormatModel();

        $reflection = new \ReflectionMethod($model, 'resolveDateCast');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($model, 'published_at', ['nullable', 'date_format:Y-m-d H:i:s']);

        $this->assertSame('datetime', $result);
    }

    public function testResolveDateCastWithDateRule(): void
    {
        $model = new CastTestModel();

        $reflection = new \ReflectionMethod($model, 'resolveDateCast');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($model, 'some_date', ['nullable', 'date']);

        $this->assertSame('datetime', $result);
    }

    public function testResolveDateCastReturnsNullForNonDateRules(): void
    {
        $model = new CastTestModel();

        $reflection = new \ReflectionMethod($model, 'resolveDateCast');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($model, 'name', ['required', 'string']);

        $this->assertNull($result);
    }

    public function testResolveDateCastSkipsNonStringRules(): void
    {
        $model = new CastTestModel();

        $reflection = new \ReflectionMethod($model, 'resolveDateCast');
        $reflection->setAccessible(true);

        // Pass an array with non-string rules
        $result = $reflection->invoke($model, 'field', [123, new \stdClass(), 'date']);

        $this->assertSame('datetime', $result);
    }

    public function testResolveDateCastStoresDateFormat(): void
    {
        $model = new CastTestModel();

        $reflection = new \ReflectionMethod($model, 'resolveDateCast');
        $reflection->setAccessible(true);

        $reflection->invoke($model, 'custom_date', ['nullable', 'date_format:d/m/Y']);

        $dateFormats = $model->getDateFormats();

        $this->assertArrayHasKey('custom_date', $dateFormats);
        $this->assertSame('d/m/Y', $dateFormats['custom_date']);
    }

    public function testGetDateOnlyFormatWithDateTimeFormat(): void
    {
        $model = new CastTestModel();

        $reflection = new \ReflectionMethod($model, 'getDateOnlyFormat');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($model);

        // Default dateFormat is 'Y-m-d H:i:s.u', so date-only should be 'Y-m-d'
        $this->assertSame('Y-m-d', $result);
    }

    public function testGetDateOnlyFormatWithDateOnlyFormat(): void
    {
        $model = new class extends SimpleModel {
            public $dateFormat = 'Y-m-d';
        };

        $reflection = new \ReflectionMethod($model, 'getDateOnlyFormat');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($model);

        $this->assertSame('Y-m-d', $result);
    }

    public function testGetDateFormatsReturnsStoredFormats(): void
    {
        $model = new CastTestModel();

        // The model should have date formats set during initialization
        $dateFormats = $model->getDateFormats();

        $this->assertIsArray($dateFormats);
        $this->assertArrayHasKey('birth_date', $dateFormats);
    }

    public function testDateFieldReturnsFormattedStringByDefault(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->birth_date = '1990-05-15';
        $model->save();

        $model->refresh();

        // Default behavior is to return formatted string
        $this->assertSame('1990-05-15', $model->birth_date);
    }

    public function testDateFieldReturnsCarbonWhenEnabled(): void
    {
        $model = new class extends CastTestModel {
            protected bool $carbonInstanceInFieldDates = true;
        };

        $model->name = 'Test';
        $model->birth_date = '1990-05-15';
        $model->save();

        $model->refresh();

        $this->assertInstanceOf(Carbon::class, $model->birth_date);
    }

    public function testDateTimeFieldReturnsFormattedString(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->published_at = '2024-01-15 10:30:00';
        $model->save();

        $model->refresh();

        // Should return a formatted string
        $this->assertNotNull($model->published_at);
    }

    public function testGetAttributeFormatsDateWhenNotCarbonMode(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->birth_date = '1990-05-15';
        $model->save();

        $model->refresh();

        // getAttribute should return formatted string, not Carbon
        $value = $model->getAttribute('birth_date');

        $this->assertIsString($value);
        $this->assertSame('1990-05-15', $value);
    }
}
