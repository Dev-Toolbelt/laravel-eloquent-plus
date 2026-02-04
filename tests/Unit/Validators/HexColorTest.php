<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Unit\Validators;

use DevToolbelt\LaravelEloquentPlus\Tests\TestCase;
use DevToolbelt\LaravelEloquentPlus\Validators\HexColor;

final class HexColorTest extends TestCase
{
    private HexColor $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new HexColor();
    }

    /**
     * @dataProvider validHexColorsProvider
     */
    public function testValidHexColors(string $color): void
    {
        $failed = false;
        $failMessage = '';

        $fail = function (string $message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
            return new class {
                public function translate(): string
                {
                    return '';
                }
            };
        };

        $this->validator->validate('color', $color, $fail);

        $this->assertFalse($failed, "Color '{$color}' should be valid but got: {$failMessage}");
    }

    /**
     * @dataProvider invalidHexColorsProvider
     */
    public function testInvalidHexColors(mixed $color): void
    {
        $failed = false;

        $fail = function (string $message) use (&$failed) {
            $failed = true;
            return new class {
                public function translate(): string
                {
                    return '';
                }
            };
        };

        $this->validator->validate('color', $color, $fail);

        $this->assertTrue($failed, "Color '{$color}' should be invalid");
    }

    public function testNullValuePasses(): void
    {
        $failed = false;

        $fail = function (string $message) use (&$failed) {
            $failed = true;
            return new class {
                public function translate(): string
                {
                    return '';
                }
            };
        };

        $this->validator->validate('color', null, $fail);

        $this->assertFalse($failed, 'Null value should pass validation');
    }

    public function testEmptyStringPasses(): void
    {
        $failed = false;

        $fail = function (string $message) use (&$failed) {
            $failed = true;
            return new class {
                public function translate(): string
                {
                    return '';
                }
            };
        };

        $this->validator->validate('color', '', $fail);

        $this->assertFalse($failed, 'Empty string should pass validation');
    }

    public function testNonStringValueFails(): void
    {
        $failed = false;
        $errorMessage = '';

        $fail = function (string $message) use (&$failed, &$errorMessage) {
            $failed = true;
            $errorMessage = $message;
            return new class {
                public function translate(): string
                {
                    return '';
                }
            };
        };

        $this->validator->validate('color', 123456, $fail);

        $this->assertTrue($failed);
        $this->assertStringContainsString('hexadecimal color', $errorMessage);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validHexColorsProvider(): array
    {
        return [
            'short format with hash' => ['#FFF'],
            'short format without hash' => ['FFF'],
            'short format lowercase' => ['fff'],
            'short format mixed case' => ['AbC'],
            'full format with hash' => ['#FFFFFF'],
            'full format without hash' => ['FFFFFF'],
            'full format lowercase' => ['ffffff'],
            'full format mixed case' => ['AbCdEf'],
            'full format with numbers' => ['123456'],
            'full format mixed' => ['1A2B3C'],
            'short with numbers' => ['123'],
            'black' => ['#000'],
            'white' => ['#FFF'],
            'red' => ['#FF0000'],
            'green' => ['#00FF00'],
            'blue' => ['#0000FF'],
        ];
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidHexColorsProvider(): array
    {
        return [
            'single character' => ['F'],
            'two characters' => ['FF'],
            'four characters' => ['FFFF'],
            'five characters' => ['FFFFF'],
            'seven characters' => ['FFFFFFF'],
            'invalid character G' => ['GGG'],
            'invalid character in full' => ['GGGGGG'],
            'color name' => ['red'],
            'rgb format' => ['rgb(255,0,0)'],
            'double hash' => ['##FFF'],
            'spaces' => [' #FFF'],
            'special characters' => ['#FF!'],
        ];
    }

    public function testArrayValueFails(): void
    {
        $failed = false;

        $fail = function (string $message) use (&$failed) {
            $failed = true;
            return new class {
                public function translate(): string
                {
                    return '';
                }
            };
        };

        $this->validator->validate('color', ['#FFF'], $fail);

        $this->assertTrue($failed, 'Array value should be invalid');
    }
}
