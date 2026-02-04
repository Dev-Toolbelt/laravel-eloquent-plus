<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Casts\OnlyNumbers;
use DevToolbelt\LaravelEloquentPlus\Casts\RemoveSpecialCharacters;
use DevToolbelt\LaravelEloquentPlus\Casts\UuidToIdCast;
use DevToolbelt\LaravelEloquentPlus\LaravelEloquentPlusServiceProvider;
use DevToolbelt\LaravelEloquentPlus\ModelBase;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Validator;

final class ServiceProviderTest extends IntegrationTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelEloquentPlusServiceProvider::class,
        ];
    }

    public function testServiceProviderIsRegistered(): void
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(LaravelEloquentPlusServiceProvider::class)
        );
    }

    public function testCpfCnpjValidatorAliasIsRegistered(): void
    {
        // Valid CPF
        $validator = Validator::make(
            ['document' => '529.982.247-25'],
            ['document' => 'cpf_cnpj']
        );

        $this->assertTrue($validator->passes());

        // Invalid CPF
        $validator = Validator::make(
            ['document' => '111.111.111-11'],
            ['document' => 'cpf_cnpj']
        );

        $this->assertFalse($validator->passes());
    }

    public function testCpfOnlyValidatorAliasIsRegistered(): void
    {
        // Valid CPF
        $validator = Validator::make(
            ['document' => '529.982.247-25'],
            ['document' => 'cpf']
        );

        $this->assertTrue($validator->passes());

        // CNPJ should fail cpf-only validation
        $validator = Validator::make(
            ['document' => '11.444.777/0001-61'],
            ['document' => 'cpf']
        );

        $this->assertFalse($validator->passes());
    }

    public function testCnpjOnlyValidatorAliasIsRegistered(): void
    {
        // Valid CNPJ
        $validator = Validator::make(
            ['document' => '11.444.777/0001-61'],
            ['document' => 'cnpj']
        );

        $this->assertTrue($validator->passes());

        // CPF should fail cnpj-only validation
        $validator = Validator::make(
            ['document' => '529.982.247-25'],
            ['document' => 'cnpj']
        );

        $this->assertFalse($validator->passes());
    }

    public function testHexColorValidatorAliasIsRegistered(): void
    {
        // Valid hex color
        $validator = Validator::make(
            ['color' => '#FF5733'],
            ['color' => 'hex_color']
        );

        $this->assertTrue($validator->passes());

        // Invalid hex color
        $validator = Validator::make(
            ['color' => 'not-a-color'],
            ['color' => 'hex_color']
        );

        $this->assertFalse($validator->passes());
    }

    public function testCastAliasesAreRegistered(): void
    {
        $aliases = ModelBase::getCastAliases();

        $this->assertArrayHasKey('only_numbers', $aliases);
        $this->assertArrayHasKey('remove_special_chars', $aliases);
        $this->assertArrayHasKey('uuid_to_id', $aliases);

        $this->assertSame(OnlyNumbers::class, $aliases['only_numbers']);
        $this->assertSame(RemoveSpecialCharacters::class, $aliases['remove_special_chars']);
        $this->assertSame(UuidToIdCast::class, $aliases['uuid_to_id']);
    }

    public function testCastAliasWithoutParametersIsResolved(): void
    {
        // Register the aliases first
        ModelBase::registerCastAliases([
            'only_numbers' => OnlyNumbers::class,
        ]);

        $model = new class extends ModelBase {
            protected $table = 'test_models';
            protected $casts = [
                'phone' => 'only_numbers',
            ];
        };

        $casts = $model->getCasts();

        $this->assertSame(OnlyNumbers::class, $casts['phone']);
    }

    public function testCastAliasWithParametersIsResolved(): void
    {
        ModelBase::registerCastAliases([
            'uuid_to_id' => UuidToIdCast::class,
        ]);

        $model = new class extends ModelBase {
            protected $table = 'test_models';
            protected $casts = [
                'category_id' => 'uuid_to_id:categories,external_id',
            ];
        };

        $casts = $model->getCasts();

        $this->assertSame(UuidToIdCast::class . ':categories,external_id', $casts['category_id']);
    }

    public function testNonAliasedCastsAreUnchanged(): void
    {
        $model = new class extends ModelBase {
            protected $table = 'test_models';
            protected $casts = [
                'is_active' => 'boolean',
                'quantity' => 'integer',
                'price' => 'float',
            ];
        };

        $casts = $model->getCasts();

        $this->assertSame('boolean', $casts['is_active']);
        $this->assertSame('integer', $casts['quantity']);
        $this->assertSame('float', $casts['price']);
    }

    public function testValidationErrorMessagesAreSet(): void
    {
        $validator = Validator::make(
            ['document' => 'invalid'],
            ['document' => 'cpf_cnpj']
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('CPF or CNPJ', $validator->errors()->first('document'));
    }

    public function testGetCastAliasesReturnsRegisteredAliases(): void
    {
        $expectedAliases = LaravelEloquentPlusServiceProvider::getCastAliases();

        $this->assertIsArray($expectedAliases);
        $this->assertNotEmpty($expectedAliases);
    }
}
