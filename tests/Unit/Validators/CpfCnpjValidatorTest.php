<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Unit\Validators;

use DevToolbelt\LaravelEloquentPlus\Tests\TestCase;
use DevToolbelt\LaravelEloquentPlus\Validators\CpfCnpjValidator;

final class CpfCnpjValidatorTest extends TestCase
{
    private CpfCnpjValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CpfCnpjValidator();
    }

    /**
     * @dataProvider validCpfProvider
     */
    public function testValidCpf(string $cpf): void
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

        $this->validator->validate('document', $cpf, $fail);

        $this->assertFalse($failed, "CPF '{$cpf}' should be valid but got: {$failMessage}");
    }

    /**
     * @dataProvider invalidCpfProvider
     */
    public function testInvalidCpf(string $cpf): void
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

        $this->validator->validate('document', $cpf, $fail);

        $this->assertTrue($failed, "CPF '{$cpf}' should be invalid");
    }

    /**
     * @dataProvider validCnpjProvider
     */
    public function testValidCnpj(string $cnpj): void
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

        $this->validator->validate('document', $cnpj, $fail);

        $this->assertFalse($failed, "CNPJ '{$cnpj}' should be valid but got: {$failMessage}");
    }

    /**
     * @dataProvider invalidCnpjProvider
     */
    public function testInvalidCnpj(string $cnpj): void
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

        $this->validator->validate('document', $cnpj, $fail);

        $this->assertTrue($failed, "CNPJ '{$cnpj}' should be invalid");
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

        $this->validator->validate('document', null, $fail);

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

        $this->validator->validate('document', '', $fail);

        $this->assertFalse($failed, 'Empty string should pass validation');
    }

    public function testInvalidLengthFails(): void
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

        $this->validator->validate('document', '123456', $fail);

        $this->assertTrue($failed);
        $this->assertStringContainsString('11 digits', $errorMessage);
        $this->assertStringContainsString('14 digits', $errorMessage);
    }

    public function testCpfWithRepeatedDigitsFails(): void
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

        $this->validator->validate('document', '11111111111', $fail);

        $this->assertTrue($failed, 'CPF with all repeated digits should be invalid');
    }

    public function testCnpjWithRepeatedDigitsFails(): void
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

        $this->validator->validate('document', '11111111111111', $fail);

        $this->assertTrue($failed, 'CNPJ with all repeated digits should be invalid');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validCpfProvider(): array
    {
        return [
            'cpf without formatting' => ['52998224725'],
            'cpf with dots and dash' => ['529.982.247-25'],
            'cpf with spaces' => [' 529.982.247-25 '],
            'another valid cpf' => ['12345678909'],
            'formatted cpf' => ['123.456.789-09'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidCpfProvider(): array
    {
        return [
            'wrong check digit' => ['52998224726'],
            'wrong check digit formatted' => ['529.982.247-26'],
            'all zeros' => ['00000000000'],
            'all twos' => ['22222222222'],
            'invalid first check digit' => ['12345678900'],
            'invalid second check digit' => ['12345678919'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validCnpjProvider(): array
    {
        return [
            'cnpj without formatting' => ['11222333000181'],
            'cnpj with formatting' => ['11.222.333/0001-81'],
            'cnpj with spaces' => [' 11.222.333/0001-81 '],
            'another valid cnpj' => ['11444777000161'],
            'formatted cnpj' => ['11.444.777/0001-61'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidCnpjProvider(): array
    {
        return [
            'wrong check digit' => ['11222333000182'],
            'wrong check digit formatted' => ['11.222.333/0001-82'],
            'all zeros' => ['00000000000000'],
            'all ones' => ['11111111111111'],
            'invalid first check digit' => ['11444777000162'],
            'invalid second check digit' => ['11444777000171'],
        ];
    }
}
