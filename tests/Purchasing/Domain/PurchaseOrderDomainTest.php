<?php

namespace App\Tests\Purchasing\Domain;

use App\Customer\Domain\Model\Address\Address;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Shared\Domain\ValueObject\ShippingMethod;
use PHPUnit\Framework\TestCase;

class PurchaseOrderDomainTest extends TestCase
{
    private function stubCustomerOrder(): CustomerOrder
    {
        $address = $this->createStub(Address::class);

        $order = $this->createStub(CustomerOrder::class);
        $order->method('getShippingAddress')->willReturn($address);
        $order->method('getShippingMethod')->willReturn(ShippingMethod::THREE_DAY);
        $order->method('getCustomerOrderRef')->willReturn('REF-123');
        $order->method('addPurchaseOrder')->willReturnSelf();

        return $order;
    }

    private function stubSupplier(): Supplier
    {
        return $this->createStub(Supplier::class);
    }

    /**
     * @param numeric-string $price
     */
    private function stubPurchaseOrderItem(
        PurchaseOrderStatus $status = PurchaseOrderStatus::PENDING,
        string $price = '10.00',
        int $quantity = 1,
        int $weight = 100,
    ): PurchaseOrderItem {
        $item = $this->createStub(PurchaseOrderItem::class);
        $item->method('getStatus')->willReturn($status);
        $item->method('getPrice')->willReturn($price);
        $item->method('getTotalPrice')->willReturn(bcmul($price, (string) $quantity, 2));
        $item->method('getTotalPriceIncVat')->willReturn(bcmul($price, (string) $quantity, 2));
        $item->method('getWeight')->willReturn($weight);
        $item->method('getTotalWeight')->willReturn($weight * $quantity);
        $item->method('getQuantity')->willReturn($quantity);

        $customerOrderItem = $this->createStub(CustomerOrderItem::class);
        $customerOrderItem->method('getPrice')->willReturn($price);
        $item->method('getCustomerOrderItem')->willReturn($customerOrderItem);

        return $item;
    }

    public function testCreateFromOrderSetsDefaultStatus(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
    }

    public function testCreateFromOrderCopiesShippingFromCustomerOrder(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        self::assertSame(ShippingMethod::THREE_DAY, $purchaseOrder->getShippingMethod());
        self::assertSame('REF-123', $purchaseOrder->getOrderRef());
    }

    public function testGenerateStatusReturnsDefaultWhenNoItems(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        $purchaseOrder->generateStatus();

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
    }

    public function testGenerateStatusUsesLowestItemStatus(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        // Add items with different statuses
        $item1 = $this->stubPurchaseOrderItem(PurchaseOrderStatus::PROCESSING);
        $item2 = $this->stubPurchaseOrderItem(PurchaseOrderStatus::PENDING); // Lowest level

        // Use reflection to add items without triggering recalculation loops
        $reflection = new \ReflectionClass($purchaseOrder);
        $itemsProperty = $reflection->getProperty('purchaseOrderItems');
        $items = $itemsProperty->getValue($purchaseOrder);
        $items->add($item1);
        $items->add($item2);

        // First transition to PROCESSING to allow testing
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setValue($purchaseOrder, PurchaseOrderStatus::PROCESSING);

        $purchaseOrder->generateStatus();

        // Should use lowest level (PENDING = 1)
        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
    }

    public function testGenerateStatusEmitsEventOnChange(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        // Clear any events from creation
        $purchaseOrder->releaseDomainEvents();

        // Add a PROCESSING item
        $item = $this->stubPurchaseOrderItem(PurchaseOrderStatus::PROCESSING);

        $reflection = new \ReflectionClass($purchaseOrder);
        $itemsProperty = $reflection->getProperty('purchaseOrderItems');
        $items = $itemsProperty->getValue($purchaseOrder);
        $items->add($item);

        $purchaseOrder->generateStatus();

        $events = $purchaseOrder->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PurchaseOrderStatusWasChangedEvent::class, $events[0]);
    }

    public function testGenerateStatusNoEventWhenUnchanged(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        // Add a PENDING item (same as default)
        $item = $this->stubPurchaseOrderItem(PurchaseOrderStatus::PENDING);

        $reflection = new \ReflectionClass($purchaseOrder);
        $itemsProperty = $reflection->getProperty('purchaseOrderItems');
        $items = $itemsProperty->getValue($purchaseOrder);
        $items->add($item);

        // Clear any events
        $purchaseOrder->releaseDomainEvents();

        $purchaseOrder->generateStatus();

        $events = $purchaseOrder->releaseDomainEvents();
        self::assertCount(0, $events);
    }

    public function testRecalculateTotalAggregatesItemPrices(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        // Add items with known prices
        $item1 = $this->stubPurchaseOrderItem(price: '10.00', quantity: 2); // 20.00
        $item2 = $this->stubPurchaseOrderItem(price: '15.00', quantity: 3); // 45.00

        $reflection = new \ReflectionClass($purchaseOrder);
        $itemsProperty = $reflection->getProperty('purchaseOrderItems');
        $items = $itemsProperty->getValue($purchaseOrder);
        $items->add($item1);
        $items->add($item2);

        $purchaseOrder->recalculateTotal();

        // 20 + 45 + shipping (3.99 from THREE_DAY)
        self::assertSame('68.99', $purchaseOrder->getTotalPrice());
    }

    public function testRecalculateTotalAggregatesWeights(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        $item1 = $this->stubPurchaseOrderItem(quantity: 2, weight: 100); // 200
        $item2 = $this->stubPurchaseOrderItem(quantity: 1, weight: 150); // 150

        $reflection = new \ReflectionClass($purchaseOrder);
        $itemsProperty = $reflection->getProperty('purchaseOrderItems');
        $items = $itemsProperty->getValue($purchaseOrder);
        $items->add($item1);
        $items->add($item2);

        $purchaseOrder->recalculateTotal();

        self::assertSame(350, $purchaseOrder->getTotalWeight());
    }

    public function testCalculateProfitReturnsRevenueMinusCost(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        // Item: qty 2, cost 10.00 each, sell price 15.00 each
        // Revenue = 2 * 15 = 30, Cost = 2 * 10 = 20, Profit = 10
        $item = $this->createStub(PurchaseOrderItem::class);
        $item->method('getQuantity')->willReturn(2);
        $item->method('getTotalPrice')->willReturn('20.00'); // Cost

        $customerOrderItem = $this->createStub(CustomerOrderItem::class);
        $customerOrderItem->method('getPrice')->willReturn('15.00'); // Sell price
        $item->method('getCustomerOrderItem')->willReturn($customerOrderItem);

        $reflection = new \ReflectionClass($purchaseOrder);
        $itemsProperty = $reflection->getProperty('purchaseOrderItems');
        $items = $itemsProperty->getValue($purchaseOrder);
        $items->add($item);

        $profit = $purchaseOrder->calculateProfit();

        self::assertSame('10.00', $profit);
    }

    public function testCalculateProfitWithMultipleItems(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        // Item 1: qty 2, cost 10 each, sell 15 each → profit 10
        $item1 = $this->createStub(PurchaseOrderItem::class);
        $item1->method('getQuantity')->willReturn(2);
        $item1->method('getTotalPrice')->willReturn('20.00');
        $coi1 = $this->createStub(CustomerOrderItem::class);
        $coi1->method('getPrice')->willReturn('15.00');
        $item1->method('getCustomerOrderItem')->willReturn($coi1);

        // Item 2: qty 3, cost 5 each, sell 8 each → profit 9
        $item2 = $this->createStub(PurchaseOrderItem::class);
        $item2->method('getQuantity')->willReturn(3);
        $item2->method('getTotalPrice')->willReturn('15.00');
        $coi2 = $this->createStub(CustomerOrderItem::class);
        $coi2->method('getPrice')->willReturn('8.00');
        $item2->method('getCustomerOrderItem')->willReturn($coi2);

        $reflection = new \ReflectionClass($purchaseOrder);
        $itemsProperty = $reflection->getProperty('purchaseOrderItems');
        $items = $itemsProperty->getValue($purchaseOrder);
        $items->add($item1);
        $items->add($item2);

        $profit = $purchaseOrder->calculateProfit();

        self::assertSame('19.00', $profit);
    }

    public function testForceRewindToPendingResetsStatus(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        // Set to a different status
        $reflection = new \ReflectionClass($purchaseOrder);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setValue($purchaseOrder, PurchaseOrderStatus::PROCESSING);

        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrder->getStatus());

        $purchaseOrder->forceRewindToPending();

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
    }

    public function testAllowEditOnlyWhenPending(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        self::assertTrue($purchaseOrder->allowEdit());

        $reflection = new \ReflectionClass($purchaseOrder);
        $statusProperty = $reflection->getProperty('status');

        $statusProperty->setValue($purchaseOrder, PurchaseOrderStatus::PROCESSING);
        self::assertFalse($purchaseOrder->allowEdit());

        $statusProperty->setValue($purchaseOrder, PurchaseOrderStatus::SHIPPED);
        self::assertFalse($purchaseOrder->allowEdit());
    }

    public function testGetLineCountReturnsItemCount(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        self::assertSame(0, $purchaseOrder->getLineCount());

        $reflection = new \ReflectionClass($purchaseOrder);
        $itemsProperty = $reflection->getProperty('purchaseOrderItems');
        $items = $itemsProperty->getValue($purchaseOrder);
        $items->add($this->stubPurchaseOrderItem());
        $items->add($this->stubPurchaseOrderItem());

        self::assertSame(2, $purchaseOrder->getLineCount());
    }

    public function testGetItemCountSumsQuantities(): void
    {
        $purchaseOrder = PurchaseOrder::createFromOrder(
            $this->stubCustomerOrder(),
            $this->stubSupplier(),
        );

        $item1 = $this->stubPurchaseOrderItem(quantity: 5);
        $item2 = $this->stubPurchaseOrderItem(quantity: 3);

        $reflection = new \ReflectionClass($purchaseOrder);
        $itemsProperty = $reflection->getProperty('purchaseOrderItems');
        $items = $itemsProperty->getValue($purchaseOrder);
        $items->add($item1);
        $items->add($item2);

        self::assertSame(8, $purchaseOrder->getItemCount());
    }
}
