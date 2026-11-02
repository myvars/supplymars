<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\Domain;

use App\Customer\Domain\Model\Address\Address;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\ShippingMethod;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class PurchaseOrderDomainTest extends TestCase
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
     * @param numeric-string      $price
     * @param numeric-string|null $sellPrice
     */
    private function stubPurchaseOrderItem(
        PurchaseOrderStatus $status = PurchaseOrderStatus::PENDING,
        string $price = '10.00',
        int $quantity = 1,
        int $weight = 100,
        ?string $sellPrice = null,
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
        $customerOrderItem->method('getPrice')->willReturn($sellPrice ?? $price);
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
        $purchaseOrder = $this->purchaseOrder();

        // A PROCESSING item first moves the PO to PROCESSING
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(PurchaseOrderStatus::PROCESSING));
        $purchaseOrder->generateStatus();
        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrder->getStatus());

        // An ACCEPTED item (higher level) leaves it at PROCESSING
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(PurchaseOrderStatus::ACCEPTED));
        $purchaseOrder->generateStatus();
        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrder->getStatus());

        // Adding a PENDING item (lowest level) takes it back to PENDING
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(PurchaseOrderStatus::PENDING));
        $purchaseOrder->generateStatus();

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
    }

    public function testGenerateStatusEmitsEventOnChange(): void
    {
        $purchaseOrder = $this->purchaseOrder();

        // Clear any events from creation
        $purchaseOrder->releaseDomainEvents();

        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(PurchaseOrderStatus::PROCESSING));
        $purchaseOrder->generateStatus();

        $events = $purchaseOrder->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PurchaseOrderStatusWasChangedEvent::class, $events[0]);
    }

    public function testGenerateStatusNoEventWhenUnchanged(): void
    {
        $purchaseOrder = $this->purchaseOrder();

        // Add a PENDING item (same as default)
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(PurchaseOrderStatus::PENDING));

        // Clear any events
        $purchaseOrder->releaseDomainEvents();

        $purchaseOrder->generateStatus();

        $events = $purchaseOrder->releaseDomainEvents();
        self::assertCount(0, $events);
    }

    public function testRecalculateTotalAggregatesItemPrices(): void
    {
        $purchaseOrder = $this->purchaseOrder();

        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(price: '10.00', quantity: 2)); // 20.00
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(price: '15.00', quantity: 3)); // 45.00

        $purchaseOrder->recalculateTotal();

        // 20 + 45 + shipping (3.99 from THREE_DAY)
        self::assertSame('68.99', $purchaseOrder->getTotalPrice());
    }

    public function testRecalculateTotalAggregatesWeights(): void
    {
        $purchaseOrder = $this->purchaseOrder();

        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(quantity: 2, weight: 100)); // 200
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(quantity: 1, weight: 150)); // 150

        $purchaseOrder->recalculateTotal();

        self::assertSame(350, $purchaseOrder->getTotalWeight());
    }

    public function testCalculateProfitReturnsRevenueMinusCost(): void
    {
        $purchaseOrder = $this->purchaseOrder();

        // Item: qty 2, cost 10.00 each, sell price 15.00 each
        // Revenue = 2 * 15 = 30, Cost = 2 * 10 = 20, Profit = 10
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(price: '10.00', quantity: 2, sellPrice: '15.00'));

        self::assertSame('10.00', $purchaseOrder->calculateProfit());
    }

    public function testCalculateProfitWithMultipleItems(): void
    {
        $purchaseOrder = $this->purchaseOrder();

        // Item 1: qty 2, cost 10 each, sell 15 each → profit 10
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(price: '10.00', quantity: 2, sellPrice: '15.00'));
        // Item 2: qty 3, cost 5 each, sell 8 each → profit 9
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(price: '5.00', quantity: 3, sellPrice: '8.00'));

        self::assertSame('19.00', $purchaseOrder->calculateProfit());
    }

    public function testForceRewindToPendingResetsStatus(): void
    {
        $purchaseOrder = $this->purchaseOrder();
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(PurchaseOrderStatus::PROCESSING));
        $purchaseOrder->generateStatus();

        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrder->getStatus());

        $purchaseOrder->forceRewindToPending();

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
    }

    /**
     * @param list<PurchaseOrderStatus> $path the statuses the PO's item moves through
     */
    #[TestWith([[], PurchaseOrderStatus::PENDING, true], 'pending')]
    #[TestWith([[PurchaseOrderStatus::PROCESSING], PurchaseOrderStatus::PROCESSING, false], 'processing')]
    #[TestWith([[PurchaseOrderStatus::PROCESSING, PurchaseOrderStatus::ACCEPTED, PurchaseOrderStatus::SHIPPED], PurchaseOrderStatus::SHIPPED, false], 'shipped')]
    public function testAllowEditOnlyWhenPending(array $path, PurchaseOrderStatus $status, bool $allowEdit): void
    {
        $purchaseOrder = $this->purchaseOrderAt(...$path);

        self::assertSame($status, $purchaseOrder->getStatus());
        self::assertSame($allowEdit, $purchaseOrder->allowEdit());
    }

    public function testGetLineCountReturnsItemCount(): void
    {
        $purchaseOrder = $this->purchaseOrder();

        self::assertSame(0, $purchaseOrder->getLineCount());

        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem());
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem());

        self::assertSame(2, $purchaseOrder->getLineCount());
    }

    public function testGetItemCountSumsQuantities(): void
    {
        $purchaseOrder = $this->purchaseOrder();

        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(quantity: 5));
        $purchaseOrder->addPurchaseOrderItem($this->stubPurchaseOrderItem(quantity: 3));

        self::assertSame(8, $purchaseOrder->getItemCount());
    }

    private function purchaseOrder(): PurchaseOrder
    {
        return PurchaseOrder::createFromOrder($this->stubCustomerOrder(), $this->stubSupplier());
    }

    /**
     * A PO with one item that moves through $path, the PO generating its status from it at each step: the
     * transitions allowed from PENDING are the only way to reach a later status.
     */
    private function purchaseOrderAt(PurchaseOrderStatus ...$path): PurchaseOrder
    {
        $status = PurchaseOrderStatus::PENDING;

        $item = $this->createStub(PurchaseOrderItem::class);
        $item->method('getStatus')->willReturnCallback(static function () use (&$status): PurchaseOrderStatus {
            return $status;
        });
        $item->method('getTotalPrice')->willReturn('10.00');
        $item->method('getTotalPriceIncVat')->willReturn('10.00');

        $purchaseOrder = $this->purchaseOrder();
        $purchaseOrder->addPurchaseOrderItem($item);

        foreach ($path as $step) {
            $status = $step;
            $purchaseOrder->generateStatus();
        }

        return $purchaseOrder;
    }
}
