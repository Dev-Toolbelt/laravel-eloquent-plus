<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Unit\Casts;

use DevToolbelt\LaravelEloquentPlus\Casts\RemoveSpecialCharacters;
use DevToolbelt\LaravelEloquentPlus\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Mockery;

final class RemoveSpecialCharactersTest extends TestCase
{
    private RemoveSpecialCharacters $cast;
    private Model $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new RemoveSpecialCharacters();
        $this->model = Mockery::mock(Model::class);
    }

    /**
     * @dataProvider setValueProvider
     */
    public function testSetRemovesSpecialCharacters(mixed $input, ?string $expected): void
    {
        $result = $this->cast->set($this->model, 'username', $input, []);

        $this->assertSame($expected, $result);
    }

    public function testGetReturnsValueUnchanged(): void
    {
        $value = 'John Doe';
        $result = $this->cast->get($this->model, 'username', $value, []);

        $this->assertSame($value, $result);
    }

    public function testGetReturnsNullWhenValueIsNull(): void
    {
        $result = $this->cast->get($this->model, 'username', null, []);

        $this->assertNull($result);
    }

    public function testSetWithNullReturnsNull(): void
    {
        $result = $this->cast->set($this->model, 'username', null, []);

        $this->assertNull($result);
    }

    public function testSetWithEmptyStringReturnsNull(): void
    {
        $result = $this->cast->set($this->model, 'username', '', []);

        $this->assertNull($result);
    }

    /**
     * @return array<string, array{0: mixed, 1: string|null}>
     */
    public static function setValueProvider(): array
    {
        return [
            'hello world with punctuation' => ['Hello, World!', 'Hello World'],
            'email format' => ['user@name#123', 'username123'],
            'parentheses' => ['Test (value)', 'Test value'],
            'multiple special chars' => ['!@#$%^&*()test', 'test'],
            'underscores and dashes' => ['hello_world-test', 'helloworldtest'],
            'quotes' => ['"Hello" \'World\'', 'Hello World'],
            'brackets' => ['[array] {object}', 'array object'],
            'only alphanumeric' => ['abc123', 'abc123'],
            'only special chars' => ['!@#$%^&*()', ''],
            'whitespace preserved' => ['Hello   World', 'Hello   World'],
            'mixed case' => ['HeLLo WoRLD 123', 'HeLLo WoRLD 123'],
            'numbers with symbols' => ['$1,234.56', '123456'],
            'percentage' => ['50%', '50'],
        ];
    }
}
