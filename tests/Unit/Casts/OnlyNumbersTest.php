<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Unit\Casts;

use DevToolbelt\LaravelEloquentPlus\Casts\OnlyNumbers;
use DevToolbelt\LaravelEloquentPlus\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Mockery;

final class OnlyNumbersTest extends TestCase
{
    private OnlyNumbers $cast;
    private Model $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new OnlyNumbers();
        $this->model = Mockery::mock(Model::class);
    }

    /**
     * @dataProvider setValueProvider
     */
    public function testSetRemovesNonNumericCharacters(mixed $input, ?string $expected): void
    {
        $result = $this->cast->set($this->model, 'phone', $input, []);

        $this->assertSame($expected, $result);
    }

    public function testGetReturnsValueUnchanged(): void
    {
        $value = '11999999999';
        $result = $this->cast->get($this->model, 'phone', $value, []);

        $this->assertSame($value, $result);
    }

    public function testGetReturnsNullWhenValueIsNull(): void
    {
        $result = $this->cast->get($this->model, 'phone', null, []);

        $this->assertNull($result);
    }

    public function testSetWithNullReturnsNull(): void
    {
        $result = $this->cast->set($this->model, 'phone', null, []);

        $this->assertNull($result);
    }

    public function testSetWithEmptyStringReturnsNull(): void
    {
        $result = $this->cast->set($this->model, 'phone', '', []);

        $this->assertNull($result);
    }

    /**
     * @return array<string, array{0: mixed, 1: string|null}>
     */
    public static function setValueProvider(): array
    {
        return [
            'phone with formatting' => ['(11) 99999-9999', '11999999999'],
            'cpf with formatting' => ['123.456.789-00', '12345678900'],
            'cnpj with formatting' => ['12.345.678/0001-00', '12345678000100'],
            'zip code with dash' => ['01310-100', '01310100'],
            'only numbers' => ['12345678', '12345678'],
            'mixed letters and numbers' => ['abc123def456', '123456'],
            'special characters' => ['!@#$%123^&*', '123'],
            'spaces only' => ['   ', ''],
            'unicode characters' => ['+55 (11) 9.9999-9999', '5511999999999'],
        ];
    }
}
