<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration\Concerns;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ExternalIdModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class HasExternalIdFullCoverageTest extends IntegrationTestCase
{
    public function testBootDoesNotGenerateExternalIdWhenDisabled(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test';
        $model->save();

        $this->assertFalse($model->usesExternalId());
        $this->assertNull($model->getExternalId());
    }

    public function testBootGeneratesExternalIdWhenEnabled(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $this->assertTrue($model->usesExternalId());
        $this->assertNotNull($model->getExternalId());
        $this->assertSame(36, strlen($model->getExternalId()));
    }

    public function testBootDoesNotOverwriteExistingExternalId(): void
    {
        $customUuid = '12345678-1234-1234-1234-123456789012';

        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->external_id = $customUuid;
        $model->save();

        $this->assertSame($customUuid, $model->getExternalId());
    }

    public function testInitializeHidesExternalIdAndPrimaryKey(): void
    {
        $model = new ExternalIdModel();

        $hidden = $model->getHidden();

        $this->assertContains('external_id', $hidden);
        $this->assertContains('id', $hidden);
    }

    public function testInitializeDoesNotHideWhenDisabled(): void
    {
        $model = new SimpleModel();

        $hidden = $model->getHidden();

        $this->assertNotContains('external_id', $hidden);
    }

    public function testUsesExternalIdReturnsTrueWhenEnabled(): void
    {
        $model = new ExternalIdModel();

        $this->assertTrue($model->usesExternalId());
    }

    public function testUsesExternalIdReturnsFalseWhenDisabled(): void
    {
        $model = new SimpleModel();

        $this->assertFalse($model->usesExternalId());
    }

    public function testGetExternalIdColumnReturnsColumnName(): void
    {
        $model = new ExternalIdModel();

        $this->assertSame('external_id', $model->getExternalIdColumn());
    }

    public function testGetExternalIdReturnsValueWhenEnabled(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $externalId = $model->getExternalId();

        $this->assertNotNull($externalId);
        $this->assertSame($model->getAttribute('external_id'), $externalId);
    }

    public function testGetExternalIdReturnsNullWhenDisabled(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test';
        $model->save();

        $this->assertNull($model->getExternalId());
    }

    public function testFindByExternalIdReturnsModelWhenFound(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $found = ExternalIdModel::findByExternalId($model->getExternalId());

        $this->assertNotNull($found);
        $this->assertSame($model->id, $found->id);
    }

    public function testFindByExternalIdReturnsNullWhenNotFound(): void
    {
        $found = ExternalIdModel::findByExternalId('non-existent-uuid');

        $this->assertNull($found);
    }

    public function testFindByExternalIdReturnsNullWhenDisabled(): void
    {
        $found = SimpleModel::findByExternalId('any-uuid');

        $this->assertNull($found);
    }

    public function testFindByExternalIdOrFailReturnsModelWhenFound(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $found = ExternalIdModel::findByExternalIdOrFail($model->getExternalId());

        $this->assertSame($model->id, $found->id);
    }

    public function testFindByExternalIdOrFailThrowsWhenNotFound(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $this->expectException(ModelNotFoundException::class);

        ExternalIdModel::findByExternalIdOrFail('non-existent-uuid');
    }

    public function testFindByExternalIdOrFailThrowsWhenDisabled(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('External ID is not enabled');

        SimpleModel::findByExternalIdOrFail('any-uuid');
    }

    public function testToArrayReplacesIdWithExternalId(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $array = $model->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertSame($model->getExternalId(), $array['id']);
    }

    public function testToSoftArrayReturnsOnlyExternalId(): void
    {
        $model = new ExternalIdModel();
        $model->name = 'Test';
        $model->save();

        $array = $model->toSoftArray();

        $this->assertCount(1, $array);
        $this->assertArrayHasKey('id', $array);
        $this->assertSame($model->getExternalId(), $array['id']);
    }
}
