<?php

namespace App\Tests\New\Unit\Entity;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Entity\PurchaseOrderItem;
use App\Enum\OrderStatus;
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

        $customerOrderItem = new CustomerOrderItem();
        $customerOrderItem->setCustomerOrder($customerOrder);
        $customerOrderItem->createFromProduct($product, 2);

        $this->assertSame($product, $customerOrderItem->getProduct());
        $this->assertSame(2, $customerOrderItem->getQuantity());
        $this->assertSame(10, $customerOrderItem->getWeight());
        $this->assertSame('100.00', $customerOrderItem->getPrice());
        $this->assertSame('120.00', $customerOrderItem->getPriceIncVat());
        $this->assertSame('200.00', $customerOrderItem->getTotalPrice());
        $this->assertSame('240.00', $customerOrderItem->getTotalPriceIncVat());
        $this->assertSame(20, $customerOrderItem->getTotalWeight());
    }

    public function testUpdateItem(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);

        $product = $this->createMock(Product::class);
        $product->method('getWeight')->willReturn(10);
        $product->method('getSellPrice')->willReturn('100.00');
        $product->method('getSellPriceIncVat')->willReturn('120.00');

        $customerOrderItem = new CustomerOrderItem();
        $customerOrderItem->setCustomerOrder($customerOrder);
        $customerOrderItem->createFromProduct($product, 2);

        $customerOrderItem->updateItem(3, '150.00', '180.00');

        $this->assertSame(3, $customerOrderItem->getQuantity());
        $this->assertSame('150.00', $customerOrderItem->getPrice());
        $this->assertSame('180.00', $customerOrderItem->getPriceIncVat());
        $this->assertSame('450.00', $customerOrderItem->getTotalPrice());
        $this->assertSame('540.00', $customerOrderItem->getTotalPriceIncVat());
    }

    public function testGenerateStatus(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);

        $product = $this->createMock(Product::class);
        $product->method('getWeight')->willReturn(10);
        $product->method('getSellPrice')->willReturn('100.00');
        $product->method('getSellPriceIncVat')->willReturn('120.00');

        $customerOrderItem = new CustomerOrderItem();
        $customerOrderItem->setCustomerOrder($customerOrder);
        $customerOrderItem->createFromProduct($product, 2);

        $this->assertSame(OrderStatus::getDefault(), $customerOrderItem->getStatus());
    }

    public function testCancelItem(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);

        $product = $this->createMock(Product::class);
        $product->method('getWeight')->willReturn(10);
        $product->method('getSellPrice')->willReturn('100.00');
        $product->method('getSellPriceIncVat')->willReturn('120.00');

        $customerOrderItem = new CustomerOrderItem();
        $customerOrderItem->setCustomerOrder($customerOrder);
        $customerOrderItem->createFromProduct($product, 2);

        $customerOrderItem->cancelItem();

        $this->assertSame(OrderStatus::CANCELLED, $customerOrderItem->getStatus());
    }

    public function testAddAndRemovePurchaseOrderItem(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);

        $product = $this->createMock(Product::class);
        $product->method('getWeight')->willReturn(10);
        $product->method('getSellPrice')->willReturn('100.00');
        $product->method('getSellPriceIncVat')->willReturn('120.00');

        $purchaseOrderItem = new PurchaseOrderItem();

        $customerOrderItem = new CustomerOrderItem();
        $customerOrderItem->setCustomerOrder($customerOrder);
        $customerOrderItem->createFromProduct($product, 2);

        $customerOrderItem->addPurchaseOrderItem($purchaseOrderItem);
        $this->assertCount(1, $customerOrderItem->getPurchaseOrderItems());

        $customerOrderItem->removePurchaseOrderItem($purchaseOrderItem);
        $this->assertCount(0, $customerOrderItem->getPurchaseOrderItems());
    }
}