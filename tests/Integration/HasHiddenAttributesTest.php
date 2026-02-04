<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class HasHiddenAttributesTest extends IntegrationTestCase
{
    public function testDeletedAtIsHiddenByDefaultWhenAttributeExists(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test';
        $model->save();

        // After save, the model has deleted_at attribute
        $model->delete();

        $hidden = $model->getHidden();

        // deleted_at is hidden if the attribute exists in the model
        $this->assertContains('deleted_at', $hidden);
    }

    public function testHiddenAttributesNotInToArray(): void
    {
        $model = new SimpleModel();
        $model->title = 'Test';
        $model->save();

        $model->delete();

        $array = SimpleModel::withTrashed()->find($model->id)->toArray();

        $this->assertArrayNotHasKey('deleted_at', $array);
    }

    public function testCustomHiddenAttributesArePreserved(): void
    {
        $model = new class extends SimpleModel {
            protected $hidden = ['title'];
        };

        $model->title = 'Test';
        $model->save();

        $hiddenAttributes = $model->getHidden();

        $this->assertContains('title', $hiddenAttributes);
    }

    public function testHiddenAttributesAreUnique(): void
    {
        $model = new class extends SimpleModel {
            protected $hidden = ['deleted_at'];
        };

        $model->title = 'Test';
        $model->save();
        $model->delete();

        $hiddenAttributes = $model->getHidden();
        $uniqueAttributes = array_unique($hiddenAttributes);

        $this->assertCount(count($uniqueAttributes), $hiddenAttributes);
    }
}
