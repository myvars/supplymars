<?php

namespace App\Tests\Purchasing\Unit;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Shared\Domain\ValueObject\ShippingMethod;
use PHPUnit\Framework\TestCase;

class PurchaseOrderTest extends TestCase
{
    public function testCreateFromOrder(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getShippingMethod')->willReturn(ShippingMethod::NEXT_DAY);
        $supplier = $this->createMock(Supplier::class);

        $purchaseOrder = PurchaseOrder::createFromOrder($customerOrder, $supplier);

        $this->assertSame($customerOrder, $purchaseOrder->getCustomerOrder());
        $this->assertSame($supplier, $purchaseOrder->getSupplier());
        $this->assertEquals(ShippingMethod::NEXT_DAY, $purchaseOrder->getShippingMethod());
    }

    public function testAddPurchaseOrderItem(): void
    {
        $purchaseOrder = $this->createPurchaseOrder();
        $item = $this->createMock(PurchaseOrderItem::class);
        $item->method('getStatus')->willReturn(PurchaseOrderStatus::getDefault());
        $item->method('getTotalPrice')->willReturn('100');
        $item->method('getTotalPriceIncVat')->willReturn('120');
        $item->method('getTotalWeight')->willReturn(10);

        // Test adding an item
        $item->expects($this->once())
            ->method('setPurchaseOrder')
            ->with($purchaseOrder);

        $purchaseOrder->addPurchaseOrderItem($item);
        $this->assertCount(1, $purchaseOrder->getPurchaseOrderItems());
        $this->assertTrue($purchaseOrder->getPurchaseOrderItems()->contains($item));
    }

    public function testRemovePurchaseOrderItem(): void
    {
        $purchaseOrder = $this->createPurchaseOrder();
        $item = $this->createMock(PurchaseOrderItem::class);
        $item->method('getStatus')->willReturn(PurchaseOrderStatus::getDefault());
        $item->method('getTotalPrice')->willReturn('100');
        $item->method('getTotalPriceIncVat')->willReturn('120');
        $item->method('getTotalWeight')->willReturn(10);

        // Add the item first to set up the state
        $purchaseOrder->addPurchaseOrderItem($item);

        // Test removing an item
        $item->expects($this->once())
            ->method('getPurchaseOrder')
            ->willReturn($purchaseOrder);

        $purchaseOrder->removePurchaseOrderItem($item);
        $this->assertCount(0, $purchaseOrder->getPurchaseOrderItems());
    }

    public function testRecalculateTotal(): void
    {
        $purchaseOrder = $this->createPurchaseOrder();

        $item1 = $this->createMock(PurchaseOrderItem::class);
        $item1->method('getStatus')->willReturn(PurchaseOrderStatus::getDefault());
        $item1->method('getTotalPrice')->willReturn('100');
        $item1->method('getTotalPriceIncVat')->willReturn('120');
        $item1->method('getTotalWeight')->willReturn(10);

        $item2 = $this->createMock(PurchaseOrderItem::class);
        $item2->method('getStatus')->willReturn(PurchaseOrderStatus::getDefault());
        $item2->method('getTotalPrice')->willReturn('200');
        $item2->method('getTotalPriceIncVat')->willReturn('240');
        $item2->method('getTotalWeight')->willReturn(20);

        $purchaseOrder->addPurchaseOrderItem($item1);
        $purchaseOrder->addPurchaseOrderItem($item2);

        $purchaseOrder->recalculateTotal();

        $this->assertEquals('309.99', $purchaseOrder->getTotalPrice());
        $this->assertEquals('369.99', $purchaseOrder->getTotalPriceIncVat());
        $this->assertEquals(30, $purchaseOrder->getTotalWeight());
    }

    public function testGenerateStatus(): void
    {
        $purchaseOrder = $this->createPurchaseOrder();

        $item1 = $this->createMock(PurchaseOrderItem::class);
        $item1->method('getStatus')->willReturn(PurchaseOrderStatus::ACCEPTED);
        $item1->method('getTotalPrice')->willReturn('100');
        $item1->method('getTotalPriceIncVat')->willReturn('120');
        $item1->method('getTotalWeight')->willReturn(10);

        $item2 = $this->createMock(PurchaseOrderItem::class);
        $item2->method('getStatus')->willReturn(PurchaseOrderStatus::PENDING);
        $item2->method('getTotalPrice')->willReturn('200');
        $item2->method('getTotalPriceIncVat')->willReturn('240');
        $item2->method('getTotalWeight')->willReturn(20);

        $purchaseOrder->addPurchaseOrderItem($item1);
        $purchaseOrder->addPurchaseOrderItem($item2);

        $purchaseOrder->generateStatus();

        $this->assertEquals(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
    }

    public function testAllowEdit(): void
    {
        $purchaseOrder = $this->createPurchaseOrder();

        $this->assertTrue($purchaseOrder->allowEdit());

        $item1 = $this->createMock(PurchaseOrderItem::class);
        $item1->method('getStatus')->willReturn(PurchaseOrderStatus::PROCESSING);
        $item1->method('getTotalPrice')->willReturn('100');
        $item1->method('getTotalPriceIncVat')->willReturn('120');
        $item1->method('getTotalWeight')->willReturn(10);

        $purchaseOrder->addPurchaseOrderItem($item1);
        $purchaseOrder->generateStatus();

        $this->assertFalse($purchaseOrder->allowEdit());
    }

    public function testGetLineCount(): void
    {
        $purchaseOrder = $this->createPurchaseOrder();

        $item1 = $this->createMock(PurchaseOrderItem::class);
        $item1->method('getStatus')->willReturn(PurchaseOrderStatus::ACCEPTED);
        $item1->method('getTotalPrice')->willReturn('100');
        $item1->method('getTotalPriceIncVat')->willReturn('120');
        $item1->method('getTotalWeight')->willReturn(10);

        $item2 = $this->createMock(PurchaseOrderItem::class);
        $item2->method('getStatus')->willReturn(PurchaseOrderStatus::PENDING);
        $item2->method('getTotalPrice')->willReturn('200');
        $item2->method('getTotalPriceIncVat')->willReturn('240');
        $item2->method('getTotalWeight')->willReturn(20);

        $purchaseOrder->addPurchaseOrderItem($item1);
        $purchaseOrder->addPurchaseOrderItem($item2);

        $this->assertEquals(2, $purchaseOrder->getLineCount());
    }

    public function testGetItemCount(): void
    {
        $purchaseOrder = $this->createPurchaseOrder();

        $item1 = $this->createMock(PurchaseOrderItem::class);
        $item1->method('getStatus')->willReturn(PurchaseOrderStatus::getDefault());
        $item1->method('getTotalPrice')->willReturn('100');
        $item1->method('getTotalPriceIncVat')->willReturn('120');
        $item1->method('getTotalWeight')->willReturn(10);
        $item1->method('getQuantity')->willReturn(1);

        $item2 = $this->createMock(PurchaseOrderItem::class);
        $item2->method('getStatus')->willReturn(PurchaseOrderStatus::getDefault());
        $item2->method('getTotalPrice')->willReturn('200');
        $item2->method('getTotalPriceIncVat')->willReturn('240');
        $item2->method('getTotalWeight')->willReturn(20);
        $item2->method('getQuantity')->willReturn(2);

        $purchaseOrder->addPurchaseOrderItem($item1);
        $purchaseOrder->addPurchaseOrderItem($item2);

        $this->assertEquals(3, $purchaseOrder->getItemCount());
    }

    private function createPurchaseOrder(): PurchaseOrder
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getShippingMethod')->willReturn(ShippingMethod::NEXT_DAY);
        $supplier = $this->createMock(Supplier::class);

        return PurchaseOrder::createFromOrder($customerOrder, $supplier);
    }
}
