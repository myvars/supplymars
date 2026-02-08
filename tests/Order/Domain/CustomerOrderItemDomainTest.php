<?php

namespace App\Tests\Order\Domain;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use PHPUnit\Framework\TestCase;

class CustomerOrderItemDomainTest extends TestCase
{
    private function stubCustomerOrder(): CustomerOrder
    {
        $order = $this->createStub(CustomerOrder::class);
        $order->method('addCustomerOrderItem')->willReturnSelf();
        $order->method('getStatus')->willReturn(OrderStatus::PENDING);

        return $order;
    }

    private function stubProduct(
        string $sellPrice = '10.00',
        string $sellPriceIncVat = '12.00',
        int $weight = 100,
    ): Product {
        $product = $this->createStub(Product::class);
        $product->method('getSellPrice')->willReturn($sellPrice);
        $product->method('getSellPriceIncVat')->willReturn($sellPriceIncVat);
        $product->method('getWeight')->willReturn($weight);

        return $product;
    }

    public function testCreateFromProductSetsDefaultStatus(): void
    {
        $item = CustomerOrderItem::createFromProduct(
            $this->stubCustomerOrder(),
            $this->stubProduct(),
            quantity: 2,
        );

        self::assertSame(OrderStatus::PENDING, $item->getStatus());
    }

    public function testCreateFromProductCalculatesTotals(): void
    {
        $item = CustomerOrderItem::createFromProduct(
            $this->stubCustomerOrder(),
            $this->stubProduct(sellPrice: '10.00', sellPriceIncVat: '12.00', weight: 100),
            quantity: 3,
        );

        self::assertSame(3, $item->getQuantity());
        self::assertSame('10.00', $item->getPrice());
        self::assertSame('30.00', $item->getTotalPrice());
        self::assertSame('36.00', $item->getTotalPriceIncVat());
        self::assertSame(300, $item->getTotalWeight());
    }

    public function testUpdateItemSucceedsWhenNoAllocatedQty(): void
    {
        $item = CustomerOrderItem::createFromProduct(
            $this->stubCustomerOrder(),
            $this->stubProduct(),
            quantity: 5,
        );

        // No PO items allocated, so any qty should work
        $item->updateItem(
            quantity: 3,
            price: '15.00',
            priceIncVat: '18.00',
            weight: 150,
        );

        self::assertSame(3, $item->getQuantity());
        self::assertSame('15.00', $item->getPrice());
        self::assertSame(150, $item->getWeight());
    }

    public function testUpdateItemRecalculatesTotals(): void
    {
        $item = CustomerOrderItem::createFromProduct(
            $this->stubCustomerOrder(),
            $this->stubProduct(),
            quantity: 2,
        );

        $item->updateItem(
            quantity: 4,
            price: '25.00',
            priceIncVat: '30.00',
            weight: 200,
        );

        self::assertSame('100.00', $item->getTotalPrice()); // 4 * 25.00
        self::assertSame('120.00', $item->getTotalPriceIncVat()); // 4 * 30.00
        self::assertSame(800, $item->getTotalWeight()); // 4 * 200
    }

    public function testUpdateItemThrowsWhenQtyBelowAllocated(): void
    {
        $item = CustomerOrderItem::createFromProduct(
            $this->stubCustomerOrder(),
            $this->stubProduct(),
            quantity: 10,
        );

        // Simulate 5 items allocated to purchase orders using reflection
        $poItem = $this->createStub(PurchaseOrderItem::class);
        $poItem->method('getQuantity')->willReturn(5);
        $poItem->method('getStatus')->willReturn(PurchaseOrderStatus::PENDING);

        $reflection = new \ReflectionClass($item);
        $poItemsProperty = $reflection->getProperty('purchaseOrderItems');
        $poItems = $poItemsProperty->getValue($item);
        $poItems->add($poItem);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot edit this allocated qty below 5');

        // Try to reduce qty below allocated amount
        $item->updateItem(
            quantity: 3, // Less than 5 allocated
            price: '10.00',
            priceIncVat: '12.00',
            weight: 100,
        );
    }

    public function testUpdateItemAllowsQtyEqualToAllocated(): void
    {
        $item = CustomerOrderItem::createFromProduct(
            $this->stubCustomerOrder(),
            $this->stubProduct(),
            quantity: 10,
        );

        // Simulate 5 items allocated
        $poItem = $this->createStub(PurchaseOrderItem::class);
        $poItem->method('getQuantity')->willReturn(5);
        $poItem->method('getStatus')->willReturn(PurchaseOrderStatus::PENDING);

        $reflection = new \ReflectionClass($item);
        $poItemsProperty = $reflection->getProperty('purchaseOrderItems');
        $poItems = $poItemsProperty->getValue($item);
        $poItems->add($poItem);

        // Should succeed - qty equals allocated
        $item->updateItem(
            quantity: 5,
            price: '10.00',
            priceIncVat: '12.00',
            weight: 100,
        );

        self::assertSame(5, $item->getQuantity());
    }

    public function testUpdateItemAllowsQtyAboveAllocated(): void
    {
        $item = CustomerOrderItem::createFromProduct(
            $this->stubCustomerOrder(),
            $this->stubProduct(),
            quantity: 10,
        );

        // Simulate 5 items allocated
        $poItem = $this->createStub(PurchaseOrderItem::class);
        $poItem->method('getQuantity')->willReturn(5);
        $poItem->method('getStatus')->willReturn(PurchaseOrderStatus::PENDING);

        $reflection = new \ReflectionClass($item);
        $poItemsProperty = $reflection->getProperty('purchaseOrderItems');
        $poItems = $poItemsProperty->getValue($item);
        $poItems->add($poItem);

        // Should succeed - qty above allocated
        $item->updateItem(
            quantity: 8,
            price: '10.00',
            priceIncVat: '12.00',
            weight: 100,
        );

        self::assertSame(8, $item->getQuantity());
    }

    public function testAllowEditDelegatesToStatus(): void
    {
        $item = CustomerOrderItem::createFromProduct(
            $this->stubCustomerOrder(),
            $this->stubProduct(),
            quantity: 1,
        );

        // Default is PENDING which allows edit
        self::assertTrue($item->allowEdit());

        // Set to SHIPPED via reflection (doesn't allow edit)
        $reflection = new \ReflectionClass($item);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setValue($item, OrderStatus::SHIPPED);

        self::assertFalse($item->allowEdit());
    }
}
