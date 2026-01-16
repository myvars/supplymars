<?php

namespace App\Tests\Order\Unit;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use PHPUnit\Framework\TestCase;

class CustomerOrderItemTest extends TestCase
{
    public function testCreateFromProduct(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);

        $product = $this->createMock(Product::class);
        $product->method('getWeight')->willReturn(10);
        $product->method('getSellPrice')->willReturn('100.00');
        $product->method('getSellPriceIncVat')->willReturn('120.00');

        $customerOrderItem = CustomerOrderItem::createFromProduct($customerOrder, $product, 2);

        $this->assertSame($product, $customerOrderItem->getProduct());
        $this->assertSame(2, $customerOrderItem->getQuantity());
        $this->assertSame(10, $customerOrderItem->getWeight());
        $this->assertSame('100.00', $customerOrderItem->getPrice());
        $this->assertSame('120.00', $customerOrderItem->getPriceIncVat());
        $this->assertSame('200.00', $customerOrderItem->getTotalPrice());
        $this->assertSame('240.00', $customerOrderItem->getTotalPriceIncVat());
        $this->assertSame(20, $customerOrderItem->getTotalWeight());
    }

    public function testQuantityMustBePositive(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);

        $product = $this->createMock(Product::class);
        $product->method('getWeight')->willReturn(10);
        $product->method('getSellPrice')->willReturn('100.00');
        $product->method('getSellPriceIncVat')->willReturn('120.00');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The quantity must be positive');
        CustomerOrderItem::createFromProduct($customerOrder, $product, -1);
    }

    public function testPriceMustBePositive(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);

        $product = $this->createMock(Product::class);
        $product->method('getWeight')->willReturn(10);
        $product->method('getSellPrice')->willReturn('-100.00');
        $product->method('getSellPriceIncVat')->willReturn('120.00');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The price must greater than 0');
        CustomerOrderItem::createFromProduct($customerOrder, $product, 1);
    }

    public function testWeightMustBePositive(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);

        $product = $this->createMock(Product::class);
        $product->method('getWeight')->willReturn(-10);
        $product->method('getSellPrice')->willReturn('100.00');
        $product->method('getSellPriceIncVat')->willReturn('120.00');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The weight must be greater than 0');
        CustomerOrderItem::createFromProduct($customerOrder, $product, 1);
    }

    public function testUpdateItem(): void
    {
        $customerOrderItem = $this->createCustomerOrderItem();

        $customerOrderItem->updateItem(3, '150.00', '180.00');

        $this->assertSame(3, $customerOrderItem->getQuantity());
        $this->assertSame('150.00', $customerOrderItem->getPrice());
        $this->assertSame('180.00', $customerOrderItem->getPriceIncVat());
        $this->assertSame('450.00', $customerOrderItem->getTotalPrice());
        $this->assertSame('540.00', $customerOrderItem->getTotalPriceIncVat());
    }

    public function testUpdateItemWithInvalidQty(): void
    {
        $customerOrderItem = $this->createCustomerOrderItem();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The quantity must be positive');
        $customerOrderItem->updateItem(0, '150.00', '180.00');
    }

    public function testGenerateStatus(): void
    {
        $customerOrderItem = $this->createCustomerOrderItem();

        $this->assertSame(OrderStatus::getDefault(), $customerOrderItem->getStatus());
    }

    public function testAllowEdit(): void
    {
        $customerOrderItem = $this->createCustomerOrderItem();

        $this->assertTrue($customerOrderItem->allowEdit());

        $customerOrderItem->cancelItem();

        $this->assertFalse($customerOrderItem->allowEdit());
    }

    public function testAllowCancel(): void
    {
        $customerOrderItem = $this->createCustomerOrderItem();

        $this->assertTrue($customerOrderItem->allowCancel());

        $customerOrderItem->cancelItem();

        $this->assertFalse($customerOrderItem->allowCancel());
    }

    public function testCancelItem(): void
    {
        $customerOrderItem = $this->createCustomerOrderItem();

        $customerOrderItem->cancelItem();

        $this->assertSame(OrderStatus::CANCELLED, $customerOrderItem->getStatus());
    }

    public function testItemCannotBeCancelled(): void
    {
        $customerOrderItem = $this->createCustomerOrderItem();

        $customerOrderItem->cancelItem();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot transition from "CANCELLED" to "CANCELLED"');
        $customerOrderItem->cancelItem();
    }

    public function testAddAndRemovePurchaseOrderItem(): void
    {
        $customerOrderItem = $this->createCustomerOrderItem();

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getStatus')->willReturn(PurchaseOrderStatus::getDefault());

        $customerOrderItem->addPurchaseOrderItem($purchaseOrderItem);
        $this->assertCount(1, $customerOrderItem->getPurchaseOrderItems());

        $customerOrderItem->removePurchaseOrderItem($purchaseOrderItem);
        $this->assertCount(0, $customerOrderItem->getPurchaseOrderItems());
    }

    public function createCustomerOrderItem(): CustomerOrderItem
    {
        $customerOrder = $this->createMock(CustomerOrder::class);

        $product = $this->createMock(Product::class);
        $product->method('getWeight')->willReturn(10);
        $product->method('getSellPrice')->willReturn('100.00');
        $product->method('getSellPriceIncVat')->willReturn('120.00');

        return CustomerOrderItem::createFromProduct($customerOrder, $product, 2);
    }
}
