<?php

namespace App\Tests\Unit\Service\Order;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\ProcessOrder;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessOrderTest extends TestCase
{
    private MockObject $createPurchaseOrderItem;

    private MockObject $changePurchaseOrderItemStatus;

    private ProcessOrder $processOrder;

    protected function setUp(): void
    {
        $this->createPurchaseOrderItem = $this->createMock(CreatePurchaseOrderItem::class);
        $this->changePurchaseOrderItemStatus = $this->createMock(ChangePurchaseOrderItemStatus::class);
        $this->processOrder = new ProcessOrder($this->createPurchaseOrderItem, $this->changePurchaseOrderItemStatus);
    }

    public function testHandleWithNonCustomerOrderEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of CustomerOrder');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->processOrder)($context);
    }

    public function testProcessOrderSuccessfully(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $product = $this->createMock(Product::class);
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $purchaseOrder = $this->createMock(PurchaseOrder::class);
        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $context = $this->createMock(CrudContext::class);

        $customerOrder->method('getCustomerOrderItems')->willReturn(new ArrayCollection([$customerOrderItem]));
        $customerOrder->method('getPurchaseOrders')->willReturn(new ArrayCollection([$purchaseOrder]));
        $customerOrderItem->method('getOutstandingQty')->willReturnOnConsecutiveCalls(5,0);
        $customerOrderItem->method('getProduct')->willReturn($product);
        $product->method('getActiveSupplierProducts')->willReturn(new ArrayCollection([$supplierProduct]));
        $supplierProduct->method('getStock')->willReturn(10);
        $supplierProduct->method('getCost')->willReturn('100.00');
        $purchaseOrder->method('getPurchaseOrderItems')->willReturn(new ArrayCollection([$purchaseOrderItem]));
        $purchaseOrderItem->method('getId')->willReturn(1);
        $purchaseOrderItem->method('getStatus')->willReturn(PurchaseOrderStatus::PENDING);
        $context->method('getEntity')->willReturn($customerOrder);

        $this->createPurchaseOrderItem->expects($this->once())->method('fromOrderItem')->with($customerOrderItem, $supplierProduct);
        $this->changePurchaseOrderItemStatus->expects($this->once())->method('fromDto');

        ($this->processOrder)($context);
    }
}
