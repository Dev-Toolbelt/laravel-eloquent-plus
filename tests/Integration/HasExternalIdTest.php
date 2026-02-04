<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ExternalIdModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class HasExternalIdTest extends IntegrationTestCase
{
    public function testExternalIdIsGeneratedOnCreate(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $this->assertNotNull($model->external_id);
        $this->assertSame(36, strlen($model->external_id));
    }

    public function testExternalIdIsUuidFormat(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression($pattern, $model->external_id);
    }

    public function testExternalIdIsNotOverwrittenIfSet(): void
    {
        $customUuid = '01234567-89ab-cdef-0123-456789abcdef';
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->external_id = $customUuid;
        $model->save();

        $this->assertSame($customUuid, $model->external_id);
    }

    public function testUsesExternalIdReturnsTrue(): void
    {
        $model = new ExternalIdModel();

        $this->assertTrue($model->usesExternalId());
    }

    public function testUsesExternalIdReturnsFalseWhenDisabled(): void
    {
        $model = new SimpleModel();

        $this->assertFalse($model->usesExternalId());
    }

    public function testGetExternalIdColumn(): void
    {
        $model = new ExternalIdModel();

        $this->assertSame('external_id', $model->getExternalIdColumn());
    }

    public function testGetExternalIdReturnsValueWhenEnabled(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $this->assertSame($model->external_id, $model->getExternalId());
    }

    public function testGetExternalIdReturnsNullWhenDisabled(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test';
        $model->save();

        $this->assertNull($model->getExternalId());
    }

    public function testFindByExternalId(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $found = ExternalIdModel::findByExternalId($model->external_id);

        $this->assertNotNull($found);
        $this->assertSame($model->id, $found->id);
        $this->assertSame($model->name, $found->name);
    }

    public function testFindByExternalIdReturnsNullWhenNotFound(): void
    {
        $found = ExternalIdModel::findByExternalId('nonexistent-uuid');

        $this->assertNull($found);
    }

    public function testFindByExternalIdReturnsNullWhenDisabled(): void
    {
        $found = SimpleModel::findByExternalId('any-uuid');

        $this->assertNull($found);
    }

    public function testFindByExternalIdOrFail(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $found = ExternalIdModel::findByExternalIdOrFail($model->external_id);

        $this->assertSame($model->id, $found->id);
    }

    public function testFindByExternalIdOrFailThrowsWhenNotFound(): void
    {
        $this->expectException(ModelNotFoundException::class);

        ExternalIdModel::findByExternalIdOrFail('nonexistent-uuid');
    }

    public function testFindByExternalIdOrFailThrowsWhenDisabled(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('External ID is not enabled');

        SimpleModel::findByExternalIdOrFail('any-uuid');
    }

    public function testToArrayExposesExternalIdAsId(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $array = $model->toArray();

        $this->assertSame($model->external_id, $array['id']);
    }

    public function testExternalIdAndPrimaryKeyAreHiddenInSerialization(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $hidden = $model->getHidden();

        $this->assertContains('external_id', $hidden);
        $this->assertContains('id', $hidden);
    }

    public function testMultipleModelsHaveUniqueExternalIds(): void
    {
        $externalIds = [];

        for ($i = 0; $i < 5; $i++) {
            $model = new ExternalIdModel();
            $model->name = "Test {$i}";
            $model->save();
            $externalIds[] = $model->external_id;
        }

        $uniqueIds = array_unique($externalIds);
        $this->assertCount(5, $uniqueIds);
    }
}
