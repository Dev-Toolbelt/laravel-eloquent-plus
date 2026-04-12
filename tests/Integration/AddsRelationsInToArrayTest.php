<?php

declare(strict_types=1);

namespace DevToolbelt\LaravelEloquentPlus\Tests\Integration;

use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\OrderModel;
use DevToolbelt\LaravelEloquentPlus\Tests\Fixtures\ProductModel;
use DevToolbelt\LaravelEloquentPlus\Tests\IntegrationTestCase;

final class AddsRelationsInToArrayTest extends IntegrationTestCase
{
    public function testDefaultsToFalse(): void
    {
        $product = new ProductModel();

        $this->assertFalse($product->addsRelationsInToArray);
    }

    public function testBelongsToRelationIsReplacedBySoftArrayWhenEnabled(): void
    {
        $product = new ProductModel();
        $product->name = 'Test Product';
        $product->save();

        $order = new OrderModel();
        $order->addsRelationsInToArray = true;
        $order->product_id = $product->id;
        $order->save();

        $order->load('product');

        $array = $order->toArray();

        $this->assertArrayHasKey('product', $array);
        $this->assertSame($product->toSoftArray(), $array['product']);
        $this->assertSame($product->getExternalId(), $array['product']['id']);
        $this->assertCount(1, $array['product']);
    }

    public function testHasManyRelationIsReplacedBySoftArrayCollectionWhenEnabled(): void
    {
        $product = new ProductModel();
        $product->addsRelationsInToArray = true;
        $product->name = 'Test Product';
        $product->save();

        $order1 = new OrderModel();
        $order1->product_id = $product->id;
        $order1->save();

        $order2 = new OrderModel();
        $order2->product_id = $product->id;
        $order2->save();

        $product->load('orders');

        $array = $product->toArray();

        $this->assertArrayHasKey('orders', $array);
        $this->assertCount(2, $array['orders']);
        $this->assertSame([$order1->toSoftArray(), $order2->toSoftArray()], $array['orders']);
        foreach ($array['orders'] as $orderArray) {
            $this->assertCount(1, $orderArray);
            $this->assertArrayHasKey('id', $orderArray);
        }
    }

    public function testNullBelongsToRelationIsKeptAsNullWhenEnabled(): void
    {
        $order = new OrderModel();
        $order->addsRelationsInToArray = true;
        $order->product_id = null;
        $order->save();

        $order->load('product');

        $array = $order->toArray();

        $this->assertArrayHasKey('product', $array);
        $this->assertNull($array['product']);
    }

    public function testEmptyHasManyCollectionIsRenderedAsEmptyArrayWhenEnabled(): void
    {
        $product = new ProductModel();
        $product->addsRelationsInToArray = true;
        $product->name = 'Lonely Product';
        $product->save();

        $product->load('orders');

        $array = $product->toArray();

        $this->assertArrayHasKey('orders', $array);
        $this->assertSame([], $array['orders']);
    }

    public function testRelationsAreNotProcessedWhenDisabled(): void
    {
        $product = new ProductModel();
        $product->name = 'Test Product';
        $product->save();

        $order = new OrderModel();
        $order->product_id = $product->id;
        $order->save();
        $order->load('product');

        $this->assertFalse($order->addsRelationsInToArray);

        $array = $order->toArray();

        $this->assertArrayHasKey('product', $array);
        $this->assertArrayHasKey('name', $array['product']);
        $this->assertSame('Test Product', $array['product']['name']);
    }

    public function testUnloadedRelationsAreNotIncluded(): void
    {
        $product = new ProductModel();
        $product->addsRelationsInToArray = true;
        $product->name = 'Test Product';
        $product->save();

        $array = $product->toArray();

        $this->assertArrayNotHasKey('orders', $array);
    }
}
