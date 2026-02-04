<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\OrderModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ProductModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;
use Illuminate\Validation\ValidationException;

final class UuidToIdCastTest extends IntegrationTestCase
{
    private ProductModel $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = new ProductModel();
        $this->product->name = 'Test Product';
        $this->product->save();
    }

    public function testConvertsUuidToIdOnSet(): void
    {
        $externalId = $this->product->getExternalId();
        $this->assertNotNull($externalId, 'Product external_id should be set');

        $order = new OrderModel();
        $order->product_id = $externalId;
        $order->save();

        $this->assertSame($this->product->id, $order->product_id);
    }

    public function testPassesThroughNumericValues(): void
    {
        $order = new OrderModel();
        $order->product_id = $this->product->id;
        $order->save();

        $this->assertSame($this->product->id, $order->product_id);
    }

    public function testPassesThroughStringNumericValues(): void
    {
        $order = new OrderModel();
        $order->product_id = (string) $this->product->id;
        $order->save();

        $this->assertSame($this->product->id, $order->product_id);
    }

    public function testReturnsNullForNullValue(): void
    {
        $order = new OrderModel();
        $order->product_id = null;

        $reflection = new \ReflectionProperty($order, 'attributes');
        $reflection->setAccessible(true);
        $attributes = $reflection->getValue($order);

        $this->assertNull($attributes['product_id'] ?? null);
    }

    public function testThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(ValidationException::class);

        $order = new OrderModel();
        $order->product_id = 'invalid-uuid-that-does-not-exist';
        $order->save();
    }

    public function testGetReturnsValueUnchanged(): void
    {
        $order = new OrderModel();
        $order->product_id = $this->product->getExternalId();
        $order->save();

        $order->refresh();

        $this->assertSame($this->product->id, $order->product_id);
    }

    public function testWorksWithMultipleRecords(): void
    {
        $product2 = new ProductModel();
        $product2->name = 'Product 2';
        $product2->save();

        $order1 = new OrderModel();
        $order1->product_id = $this->product->getExternalId();
        $order1->save();

        $order2 = new OrderModel();
        $order2->product_id = $product2->getExternalId();
        $order2->save();

        $this->assertSame($this->product->id, $order1->product_id);
        $this->assertSame($product2->id, $order2->product_id);
    }
}
