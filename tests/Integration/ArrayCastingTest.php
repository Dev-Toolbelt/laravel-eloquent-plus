<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\CastTestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Integration tests for array casting with JSON storage.
 *
 * Tests the complete flow of array fields:
 * - Setting arrays via property assignment
 * - Setting arrays via fill() method
 * - Storing as JSON in the database
 * - Retrieving as PHP arrays
 */
final class ArrayCastingTest extends IntegrationTestCase
{
    public function testArrayFieldIsCastToArrayType(): void
    {
        $model = new CastTestModel();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('metadata', $casts);
        $this->assertSame('array', $casts['metadata']);
    }

    public function testArrayFieldAcceptsArrayViaPropertyAssignment(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = ['key' => 'value'];

        $this->assertIsArray($model->metadata);
        $this->assertSame(['key' => 'value'], $model->metadata);
    }

    public function testArrayFieldAcceptsArrayViaFill(): void
    {
        $model = new CastTestModel();
        $model->fill([
            'name' => 'Test',
            'metadata' => ['setting' => true, 'count' => 10],
        ]);

        $this->assertIsArray($model->metadata);
        $this->assertSame(['setting' => true, 'count' => 10], $model->metadata);
    }

    public function testArrayFieldAcceptsArrayViaConstructor(): void
    {
        $model = new CastTestModel([
            'name' => 'Test',
            'metadata' => ['config' => ['nested' => 'value']],
        ]);

        $this->assertIsArray($model->metadata);
        $this->assertSame(['config' => ['nested' => 'value']], $model->metadata);
    }

    public function testArrayFieldIsStoredAsJsonInDatabase(): void
    {
        $metadata = ['key' => 'value', 'number' => 42, 'active' => true];

        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = $metadata;
        $model->save();

        // Query the raw database value
        $rawValue = DB::table('test_models')
            ->where('id', $model->id)
            ->value('metadata');

        // The raw value should be a JSON string
        $this->assertIsString($rawValue);
        $this->assertJson($rawValue);

        // Decode and verify the JSON structure
        $decoded = json_decode($rawValue, true);
        $this->assertSame($metadata, $decoded);
    }

    public function testArrayFieldIsRetrievedAsArrayFromDatabase(): void
    {
        $metadata = [
            'settings' => [
                'notifications' => true,
                'theme' => 'dark',
            ],
            'tags' => ['php', 'laravel', 'eloquent'],
        ];

        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = $metadata;
        $model->save();

        // Refresh from database
        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertSame($metadata, $model->metadata);
    }

    public function testArrayFieldHandlesNestedArrays(): void
    {
        $metadata = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'deepValue' => 'found',
                    ],
                ],
            ],
        ];

        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = $metadata;
        $model->save();

        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertSame('found', $model->metadata['level1']['level2']['level3']['deepValue']);
    }

    public function testArrayFieldHandlesEmptyArray(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = [];
        $model->save();

        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertEmpty($model->metadata);
    }

    public function testArrayFieldHandlesIndexedArray(): void
    {
        $metadata = ['apple', 'banana', 'cherry'];

        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = $metadata;
        $model->save();

        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertSame($metadata, $model->metadata);
        $this->assertSame('banana', $model->metadata[1]);
    }

    public function testArrayFieldHandlesMixedTypes(): void
    {
        $metadata = [
            'string' => 'text',
            'integer' => 123,
            'float' => 45.67,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
        ];

        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = $metadata;
        $model->save();

        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertSame('text', $model->metadata['string']);
        $this->assertSame(123, $model->metadata['integer']);
        $this->assertSame(45.67, $model->metadata['float']);
        $this->assertTrue($model->metadata['boolean']);
        $this->assertNull($model->metadata['null']);
        $this->assertSame([1, 2, 3], $model->metadata['array']);
    }

    public function testArrayFieldCanBeUpdated(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = ['original' => 'value'];
        $model->save();

        // Update the metadata
        $model->metadata = ['updated' => 'newValue', 'added' => true];
        $model->save();

        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertArrayNotHasKey('original', $model->metadata);
        $this->assertSame('newValue', $model->metadata['updated']);
        $this->assertTrue($model->metadata['added']);
    }

    public function testArrayFieldCanBeSetToNull(): void
    {
        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = ['some' => 'data'];
        $model->save();

        // Set to null
        $model->metadata = null;
        $model->save();

        $model->refresh();

        $this->assertNull($model->metadata);
    }

    public function testArrayFieldWorksWithModelCreate(): void
    {
        $metadata = ['created' => 'via static method'];

        $model = CastTestModel::create([
            'name' => 'Test',
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($model->metadata);
        $this->assertSame($metadata, $model->metadata);

        // Verify it was persisted
        $fresh = CastTestModel::find($model->id);
        $this->assertSame($metadata, $fresh->metadata);
    }

    public function testArrayFieldWorksWithModelUpdate(): void
    {
        $model = CastTestModel::create([
            'name' => 'Test',
            'metadata' => ['initial' => 'data'],
        ]);

        $model->update([
            'metadata' => ['updated' => 'data', 'new' => 'field'],
        ]);

        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertArrayNotHasKey('initial', $model->metadata);
        $this->assertSame('data', $model->metadata['updated']);
        $this->assertSame('field', $model->metadata['new']);
    }

    public function testArrayFieldIsProperlySerializedInToArray(): void
    {
        $metadata = ['key' => 'value', 'nested' => ['data' => true]];

        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = $metadata;
        $model->save();

        $model->refresh();

        $array = $model->toArray();

        $this->assertArrayHasKey('metadata', $array);
        $this->assertIsArray($array['metadata']);
        $this->assertSame($metadata, $array['metadata']);
    }

    public function testArrayFieldIsProperlySerializedInToJson(): void
    {
        $metadata = ['key' => 'value', 'count' => 5];

        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = $metadata;
        $model->save();

        $model->refresh();

        $json = $model->toJson();
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('metadata', $decoded);
        $this->assertIsArray($decoded['metadata']);
        $this->assertSame($metadata, $decoded['metadata']);
    }

    public function testMultipleModelsWithDifferentArrayData(): void
    {
        $model1 = CastTestModel::create([
            'name' => 'Model 1',
            'metadata' => ['type' => 'first', 'priority' => 1],
        ]);

        $model2 = CastTestModel::create([
            'name' => 'Model 2',
            'metadata' => ['type' => 'second', 'priority' => 2],
        ]);

        $model3 = CastTestModel::create([
            'name' => 'Model 3',
            'metadata' => ['type' => 'third', 'priority' => 3],
        ]);

        // Fetch fresh instances
        $fresh1 = CastTestModel::find($model1->id);
        $fresh2 = CastTestModel::find($model2->id);
        $fresh3 = CastTestModel::find($model3->id);

        $this->assertSame('first', $fresh1->metadata['type']);
        $this->assertSame(1, $fresh1->metadata['priority']);

        $this->assertSame('second', $fresh2->metadata['type']);
        $this->assertSame(2, $fresh2->metadata['priority']);

        $this->assertSame('third', $fresh3->metadata['type']);
        $this->assertSame(3, $fresh3->metadata['priority']);
    }

    public function testArrayFieldWithUnicodeContent(): void
    {
        $metadata = [
            'greeting' => 'Olá, mundo! 你好世界 🌍',
            'symbols' => '€ £ ¥ © ® ™',
            'emoji' => '😀 🎉 ✨',
        ];

        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = $metadata;
        $model->save();

        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertSame($metadata['greeting'], $model->metadata['greeting']);
        $this->assertSame($metadata['symbols'], $model->metadata['symbols']);
        $this->assertSame($metadata['emoji'], $model->metadata['emoji']);
    }

    public function testArrayFieldWithSpecialCharactersInKeys(): void
    {
        $metadata = [
            'key-with-dash' => 'value1',
            'key_with_underscore' => 'value2',
            'key.with.dots' => 'value3',
            'key:with:colons' => 'value4',
        ];

        $model = new CastTestModel();
        $model->name = 'Test';
        $model->metadata = $metadata;
        $model->save();

        $model->refresh();

        $this->assertIsArray($model->metadata);
        $this->assertSame('value1', $model->metadata['key-with-dash']);
        $this->assertSame('value2', $model->metadata['key_with_underscore']);
        $this->assertSame('value3', $model->metadata['key.with.dots']);
        $this->assertSame('value4', $model->metadata['key:with:colons']);
    }
}
