<?php

namespace App\Tests\Purchasing\Unit;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use PHPUnit\Framework\TestCase;

class PurchaseOrderItemTest extends TestCase
{
    public function testCreateFromCustomerOrderItem(): void
    {
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getOutstandingQty')->willReturn(10);
        $purchaseOrder = $this->createMock(PurchaseOrder::class);
        $supplierProduct = $this->createMock(SupplierProduct::class);

        $supplierProduct->method('getCost')->willReturn('100');
        $supplierProduct->method('getWeight')->willReturn(5);

        $item = PurchaseOrderItem::createFromCustomerOrderItem($customerOrderItem, $purchaseOrder, $supplierProduct, 5);

        $this->assertSame($purchaseOrder, $item->getPurchaseOrder());
        $this->assertSame($supplierProduct, $item->getSupplierProduct());
        $this->assertSame($customerOrderItem, $item->getCustomerOrderItem());
        $this->assertEquals(5, $item->getQuantity());
        $this->assertEquals('100', $item->getPrice());
        $this->assertEquals('100', $item->getPriceIncVat());
        $this->assertEquals(5, $item->getWeight());
        $this->assertEquals('500.00', $item->getTotalPrice());
        $this->assertEquals('500.00', $item->getTotalPriceIncVat());
        $this->assertEquals(25, $item->getTotalWeight());
    }

    public function testUpdateItem(): void
    {
        $item = $this->getPurchaseOrderItem();
        $item->updateItemQuantity(10);

        $this->assertEquals(10, $item->getQuantity());
        $this->assertEquals('1000.00', $item->getTotalPrice());
    }

    public function testInvalidQtyUpdateItem(): void
    {
        $item = $this->getPurchaseOrderItem();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The quantity must be greater than 0');
        $item->updateItemQuantity(0);
    }

    public function testUpdateStatus(): void
    {
        $item = $this->getPurchaseOrderItem();

        $item->updateStatus(PurchaseOrderStatus::PROCESSING);

        $this->assertEquals(PurchaseOrderStatus::PROCESSING, $item->getStatus());
    }

    public function testInvalidUpdateStatus(): void
    {
        $item = $this->getPurchaseOrderItem();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot transition from "PENDING" to "DELIVERED"');
        $item->updateStatus(PurchaseOrderStatus::DELIVERED);
    }

    public function testAllowEdit(): void
    {
        $item = $this->getPurchaseOrderItem();

        $this->assertTrue($item->allowEdit());

        $item->updateStatus(PurchaseOrderStatus::PROCESSING);

        $this->assertFalse($item->allowEdit());
    }

    private function getPurchaseOrderItem(): PurchaseOrderItem
    {
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getOutstandingQty')->willReturn(10);
        $purchaseOrder = $this->createMock(PurchaseOrder::class);
        $supplierProduct = $this->createMock(SupplierProduct::class);

        $supplierProduct->method('getCost')->willReturn('100');
        $supplierProduct->method('getWeight')->willReturn(5);

        return PurchaseOrderItem::createFromCustomerOrderItem($customerOrderItem, $purchaseOrder, $supplierProduct, 5);
    }
}
