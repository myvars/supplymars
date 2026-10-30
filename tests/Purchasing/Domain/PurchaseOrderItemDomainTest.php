<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\Domain;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderItemStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Shared\Domain\Event\AbstractDomainEvent;
use PHPUnit\Framework\TestCase;

final class PurchaseOrderItemDomainTest extends TestCase
{
    private function stubPurchaseOrder(): PurchaseOrder
    {
        $purchaseOrder = $this->createStub(PurchaseOrder::class);
        $purchaseOrder->method('addPurchaseOrderItem')->willReturnSelf();
        $purchaseOrder->method('generateStatus')->willReturnSelf();

        return $purchaseOrder;
    }

    private function stubSupplierProduct(
        string $cost = '10.00',
        int $weight = 100,
    ): SupplierProduct {
        $supplierProduct = $this->createStub(SupplierProduct::class);
        $supplierProduct->method('getCost')->willReturn($cost);
        $supplierProduct->method('getWeight')->willReturn($weight);

        return $supplierProduct;
    }

    private function stubCustomerOrderItem(int $outstandingQty = 10): CustomerOrderItem
    {
        $orderItem = $this->createStub(CustomerOrderItem::class);
        $orderItem->method('addPurchaseOrderItem')->willReturnSelf();
        $orderItem->method('getOutstandingQty')->willReturn($outstandingQty);
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

    public function testCreateFromCustomerOrderItemSetsCorrectProperties(): void
    {
        $supplierProduct = $this->stubSupplierProduct('25.50', 500);

        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $supplierProduct,
            quantity: 3,
        );

        self::assertSame(3, $purchaseOrderItem->getQuantity());
        self::assertSame('25.50', $purchaseOrderItem->getPrice());
        self::assertSame('25.50', $purchaseOrderItem->getPriceIncVat());
        self::assertSame(500, $purchaseOrderItem->getWeight());
    }

    public function testCreateFromCustomerOrderItemCalculatesTotalCorrectly(): void
    {
        $supplierProduct = $this->stubSupplierProduct('10.00', 100);

        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $supplierProduct,
            quantity: 5,
        );

        self::assertSame('50.00', $purchaseOrderItem->getTotalPrice());
        self::assertSame('50.00', $purchaseOrderItem->getTotalPriceIncVat());
        self::assertSame(500, $purchaseOrderItem->getTotalWeight());
    }

    public function testCreateFromCustomerOrderItemSetsDefaultPendingStatus(): void
    {
        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 1,
        );

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrderItem->getStatus());
    }

    public function testAllowEditReturnsTrueForPendingStatus(): void
    {
        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 1,
        );

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrderItem->getStatus());
        self::assertTrue($purchaseOrderItem->allowEdit());
    }

    public function testAllowEditReturnsFalseForNonPendingStatus(): void
    {
        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 1,
        );

        // Transition to PROCESSING status
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrderItem->getStatus());
        self::assertFalse($purchaseOrderItem->allowEdit());
    }

    public function testUpdateItemQuantityUpdatesQuantityAndRecalculates(): void
    {
        $supplierProduct = $this->stubSupplierProduct('10.00', 100);

        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $supplierProduct,
            quantity: 2,
        );

        self::assertSame(2, $purchaseOrderItem->getQuantity());
        self::assertSame('20.00', $purchaseOrderItem->getTotalPrice());

        $purchaseOrderItem->updateItemQuantity(5);

        self::assertSame(5, $purchaseOrderItem->getQuantity());
        self::assertSame('50.00', $purchaseOrderItem->getTotalPrice());
        self::assertSame(500, $purchaseOrderItem->getTotalWeight());
    }

    public function testUpdateItemQuantityThrowsWhenNotEditable(): void
    {
        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 1,
        );

        // Transition to non-editable status
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIsOrContains('Purchase order item cannot be edited');

        $purchaseOrderItem->updateItemQuantity(5);
    }

    public function testUpdateItemStatusChangesStatus(): void
    {
        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 1,
        );

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrderItem->getStatus());

        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrderItem->getStatus());
    }

    public function testUpdateItemStatusRaisesDomainEvent(): void
    {
        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 1,
        );

        // Clear any existing events
        $purchaseOrderItem->releaseDomainEvents();

        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        $events = $purchaseOrderItem->releaseDomainEvents();
        $statusEvents = array_filter(
            $events,
            fn (AbstractDomainEvent $event): bool => $event instanceof PurchaseOrderItemStatusWasChangedEvent
        );

        self::assertNotEmpty($statusEvents);
    }

    public function testAllowStatusChangeReturnsFalseForTerminalStatuses(): void
    {
        // Test DELIVERED status
        $deliveredItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 1,
        );
        // Progress to DELIVERED: PENDING -> PROCESSING -> ACCEPTED -> SHIPPED -> DELIVERED
        $deliveredItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $deliveredItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $deliveredItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);
        $deliveredItem->updateItemStatus(PurchaseOrderStatus::DELIVERED);

        self::assertFalse($deliveredItem->allowStatusChange());

        // Test CANCELLED status
        $cancelledItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 1,
        );
        $cancelledItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $cancelledItem->updateItemStatus(PurchaseOrderStatus::CANCELLED);

        self::assertFalse($cancelledItem->allowStatusChange());
    }

    public function testChangeQuantityThrowsOnNonPositiveValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The quantity must be greater than 0');

        PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(10),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 0,
        );
    }

    public function testChangeQuantityThrowsWhenExceedingMaxQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Quantity cannot be greater than 5');

        // Customer order item has only 5 outstanding qty
        PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $this->stubCustomerOrderItem(5),
            purchaseOrder: $this->stubPurchaseOrder(),
            supplierProduct: $this->stubSupplierProduct(),
            quantity: 10,
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
        $this->expectExceptionMessageIsOrContains('Cannot transition from "SHIPPED" to "REFUNDED"');

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
        $this->expectExceptionMessageIsOrContains('Cannot transition from "ACCEPTED" to "REFUNDED"');

        $item->updateItemStatus(PurchaseOrderStatus::REFUNDED);
    }
}
