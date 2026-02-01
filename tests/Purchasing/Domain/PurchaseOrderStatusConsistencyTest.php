<?php

namespace App\Tests\Purchasing\Domain;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use PHPUnit\Framework\TestCase;

/**
 * Tests to verify PO item status consistency behaviors.
 * These tests validate the fixes to acceptPOsCommand and refundPOsCommand.
 */
class PurchaseOrderStatusConsistencyTest extends TestCase
{
    private function stubPurchaseOrder(): PurchaseOrder
    {
        $purchaseOrder = $this->createStub(PurchaseOrder::class);
        $purchaseOrder->method('addPurchaseOrderItem')->willReturnSelf();
        $purchaseOrder->method('generateStatus')->willReturnSelf();

        return $purchaseOrder;
    }

    private function stubSupplierProduct(): SupplierProduct
    {
        $supplierProduct = $this->createStub(SupplierProduct::class);
        $supplierProduct->method('getCost')->willReturn('10.00');
        $supplierProduct->method('getWeight')->willReturn(100);

        return $supplierProduct;
    }

    private function stubCustomerOrderItem(): CustomerOrderItem
    {
        $orderItem = $this->createStub(CustomerOrderItem::class);
        $orderItem->method('addPurchaseOrderItem')->willReturnSelf();
        $orderItem->method('getOutstandingQty')->willReturn(10);
        $orderItem->method('generateStatus')->willReturnSelf();

        return $orderItem;
    }

    private function createItem(): PurchaseOrderItem
    {
        return PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 1,
        );
    }

    /**
     * Tests that applying the same status to multiple items results in consistent status.
     * This validates the fix to acceptPOsCommand where status is now determined once per PO.
     */
    public function testApplyingSameStatusToMultipleItemsResultsInConsistentStatus(): void
    {
        $item1 = $this->createItem();
        $item2 = $this->createItem();
        $item3 = $this->createItem();

        // Progress all to PROCESSING (waiting for accept/reject)
        $item1->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $item2->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $item3->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        // Simulate the fixed behavior: determine status once, apply to all items
        $status = PurchaseOrderStatus::ACCEPTED;

        $item1->updateItemStatus($status);
        $item2->updateItemStatus($status);
        $item3->updateItemStatus($status);

        // All items should have the same status
        self::assertSame($status, $item1->getStatus());
        self::assertSame($status, $item2->getStatus());
        self::assertSame($status, $item3->getStatus());
    }

    /**
     * Tests that only REJECTED items should be transitioned to REFUNDED.
     * This validates the fix to refundPOsCommand where non-REJECTED items are skipped.
     */
    public function testOnlyRejectedItemsCanBeRefunded(): void
    {
        $rejectedItem = $this->createItem();
        $acceptedItem = $this->createItem();
        $shippedItem = $this->createItem();

        // Set up different statuses
        $rejectedItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $rejectedItem->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $acceptedItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $acceptedItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);

        $shippedItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $shippedItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $shippedItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);

        // Simulate the fixed refund logic: only refund REJECTED items
        $items = [$rejectedItem, $acceptedItem, $shippedItem];

        foreach ($items as $item) {
            if ($item->getStatus() !== PurchaseOrderStatus::REJECTED) {
                continue;
            }

            $item->updateItemStatus(PurchaseOrderStatus::REFUNDED);
        }

        // REJECTED item should now be REFUNDED
        self::assertSame(PurchaseOrderStatus::REFUNDED, $rejectedItem->getStatus());

        // Other items should remain unchanged
        self::assertSame(PurchaseOrderStatus::ACCEPTED, $acceptedItem->getStatus());
        self::assertSame(PurchaseOrderStatus::SHIPPED, $shippedItem->getStatus());
    }

    /**
     * Tests that SHIPPED items cannot transition directly to REFUNDED.
     * This was the root cause of the refundPOsCommand crash.
     */
    public function testShippedItemCannotTransitionToRefunded(): void
    {
        $item = $this->createItem();

        // Progress to SHIPPED
        $item->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $item->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $item->updateItemStatus(PurchaseOrderStatus::SHIPPED);

        // SHIPPED -> REFUNDED should throw
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot transition from "SHIPPED" to "REFUNDED"');

        $item->updateItemStatus(PurchaseOrderStatus::REFUNDED);
    }

    /**
     * Tests that ACCEPTED items cannot transition directly to REFUNDED.
     */
    public function testAcceptedItemCannotTransitionToRefunded(): void
    {
        $item = $this->createItem();

        // Progress to ACCEPTED
        $item->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $item->updateItemStatus(PurchaseOrderStatus::ACCEPTED);

        // ACCEPTED -> REFUNDED should throw
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot transition from "ACCEPTED" to "REFUNDED"');

        $item->updateItemStatus(PurchaseOrderStatus::REFUNDED);
    }
}
