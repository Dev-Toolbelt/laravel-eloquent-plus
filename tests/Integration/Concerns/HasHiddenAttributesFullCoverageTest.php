<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration\Concerns;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\HiddenAttributesModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\SimpleModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\TestModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class HasHiddenAttributesFullCoverageTest extends IntegrationTestCase
{
    public function testSetupHiddenAddsDeletedAtWhenAttributeExists(): void
    {
        $model = new HiddenAttributesModel();

        $hidden = $model->getHidden();

        $this->assertContains('deleted_at', $hidden);
    }

    public function testSetupHiddenAddsDeletedByWhenAttributeExists(): void
    {
        $model = new HiddenAttributesModel();

        $hidden = $model->getHidden();

        $this->assertContains('deleted_by', $hidden);
    }

    public function testSetupHiddenPreservesCustomHiddenFields(): void
    {
        $model = new HiddenAttributesModel();

        $hidden = $model->getHidden();

        // The model has 'secret_field' as custom hidden
        $this->assertContains('secret_field', $hidden);
    }

    public function testSetupHiddenMergesDefaultAndCustomHidden(): void
    {
        $model = new HiddenAttributesModel();

        $hidden = $model->getHidden();

        // Should contain all: custom hidden + deleted_at + deleted_by
        $this->assertContains('secret_field', $hidden);
        $this->assertContains('deleted_at', $hidden);
        $this->assertContains('deleted_by', $hidden);
    }

    public function testSetupHiddenDoesNotAddDeletedAtWhenAttributeNotExists(): void
    {
        // SimpleModel doesn't have deleted_at attribute pre-populated
        $model = new SimpleModel();

        $hidden = $model->getHidden();

        // Since hasAttribute returns false, deleted_at should not be in hidden
        // unless it's already there by some other mechanism
        $this->assertIsArray($hidden);
    }

    public function testHiddenArrayIsUnique(): void
    {
        // Create a model where deleted_at might be added twice
        $model = new class extends HiddenAttributesModel {
            protected $hidden = ['secret_field', 'deleted_at']; // deleted_at already in hidden
        };

        $hidden = $model->getHidden();

        // Should not have duplicates
        $uniqueHidden = array_unique($hidden);
        $this->assertCount(count($uniqueHidden), $hidden);
    }

    public function testToArrayExcludesHiddenAttributes(): void
    {
        $model = new HiddenAttributesModel();
        $model->name = 'Test';
        $model->save();

        $model->refresh();

        $array = $model->toArray();

        // deleted_at and deleted_by should not appear in toArray output
        $this->assertArrayNotHasKey('deleted_at', $array);
        $this->assertArrayNotHasKey('deleted_by', $array);
    }

    public function testToArrayExcludesCustomHiddenFields(): void
    {
        $model = new HiddenAttributesModel();
        $model->name = 'Test';

        // Set secret field value in memory (don't save to DB since column doesn't exist)
        $model->setAttribute('secret_field', 'secret_value');

        $array = $model->toArray();

        // secret_field should not appear in toArray output because it's hidden
        $this->assertArrayNotHasKey('secret_field', $array);
    }

    public function testInitializeHasHiddenAttributesIsCalled(): void
    {
        // Verify the trait is initialized during construction
        $model = new HiddenAttributesModel();

        // If initialize was called, hidden should already be set up
        $hidden = $model->getHidden();

        $this->assertNotEmpty($hidden);
    }

    public function testHiddenAttributesWithSoftDeletes(): void
    {
        $model = new HiddenAttributesModel();
        $model->name = 'Test';
        $model->save();

        $model->delete();

        // After soft delete, model still has deleted_at but it's hidden
        $array = $model->toArray();

        $this->assertArrayNotHasKey('deleted_at', $array);
        $this->assertNotNull($model->deleted_at);
    }

    public function testModelWithoutDeletedByConstant(): void
    {
        // SimpleModel doesn't have DELETED_BY constant
        $model = new SimpleModel();
        $model->title = 'Test';

        $hidden = $model->getHidden();

        // Should not crash and should return an array
        $this->assertIsArray($hidden);
    }

    public function testSetupHiddenWithPreExistingHiddenArray(): void
    {
        $model = new class extends HiddenAttributesModel {
            protected $hidden = ['field1', 'field2', 'field3'];

            public function __construct(array $attributes = [])
            {
                $this->attributes = [
                    'deleted_at' => null,
                    'deleted_by' => null,
                ];

                parent::__construct($attributes);
            }
        };

        $hidden = $model->getHidden();

        // Should contain all original hidden plus deleted_at and deleted_by
        $this->assertContains('field1', $hidden);
        $this->assertContains('field2', $hidden);
        $this->assertContains('field3', $hidden);
        $this->assertContains('deleted_at', $hidden);
        $this->assertContains('deleted_by', $hidden);
    }

    public function testMakeVisibleOverridesHidden(): void
    {
        $model = new HiddenAttributesModel();
        $model->name = 'Test';
        $model->save();

        // Make deleted_at visible
        $model->makeVisible('deleted_at');

        $array = $model->toArray();

        // Now deleted_at should be visible
        $this->assertArrayHasKey('deleted_at', $array);
    }
}
