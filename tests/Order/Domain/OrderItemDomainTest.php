<?php

namespace App\Tests\Order\Domain;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\Event\OrderItemStatusWasChangedEvent;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Shared\Domain\Event\AbstractDomainEvent;
use PHPUnit\Framework\TestCase;

class OrderItemDomainTest extends TestCase
{
    private function stubCustomerOrder(): CustomerOrder
    {
        $order = $this->createStub(CustomerOrder::class);
        $order->method('addCustomerOrderItem')->willReturnSelf();

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

    public function testCreateFromProductSetsCorrectProperties(): void
    {
        $product = $this->stubProduct('25.50', '30.60', 500);

        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $product,
            quantity: 3,
        );

        self::assertSame(3, $orderItem->getQuantity());
        self::assertSame('25.50', $orderItem->getPrice());
        self::assertSame('30.60', $orderItem->getPriceIncVat());
        self::assertSame(500, $orderItem->getWeight());
    }

    public function testCreateFromProductCalculatesTotalCorrectly(): void
    {
        $product = $this->stubProduct('10.00', '12.00', 100);

        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $product,
            quantity: 5,
        );

        self::assertSame('50.00', $orderItem->getTotalPrice());
        self::assertSame('60.00', $orderItem->getTotalPriceIncVat());
        self::assertSame(500, $orderItem->getTotalWeight());
    }

    public function testCreateFromProductSetsDefaultPendingStatus(): void
    {
        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $this->stubProduct(),
            quantity: 1,
        );

        self::assertSame(OrderStatus::PENDING, $orderItem->getStatus());
    }

    public function testAllowEditReturnsTrueForPendingStatus(): void
    {
        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $this->stubProduct(),
            quantity: 1,
        );

        self::assertSame(OrderStatus::PENDING, $orderItem->getStatus());
        self::assertTrue($orderItem->allowEdit());
    }

    public function testAllowCancelReturnsTrueForPendingWithNoPurchaseOrders(): void
    {
        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $this->stubProduct(),
            quantity: 1,
        );

        self::assertTrue($orderItem->allowCancel());
    }

    public function testGetOutstandingQtyEqualsQuantityWithNoPurchaseOrders(): void
    {
        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $this->stubProduct(),
            quantity: 10,
        );

        self::assertSame(10, $orderItem->getOutstandingQty());
    }

    public function testCancelItemChangesStatusToCancelled(): void
    {
        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $this->stubProduct(),
            quantity: 1,
        );

        $orderItem->cancelItem();

        self::assertSame(OrderStatus::CANCELLED, $orderItem->getStatus());
    }

    public function testCancelItemRaisesDomainEvent(): void
    {
        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $this->stubProduct(),
            quantity: 1,
        );

        $orderItem->releaseDomainEvents();

        $orderItem->cancelItem();

        $events = $orderItem->releaseDomainEvents();
        $statusEvents = array_filter(
            $events,
            fn (AbstractDomainEvent $event): bool => $event instanceof OrderItemStatusWasChangedEvent
        );

        self::assertNotEmpty($statusEvents);
    }

    public function testIsCancelledReturnsTrueAfterCancel(): void
    {
        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $this->stubProduct(),
            quantity: 1,
        );

        $orderItem->cancelItem();

        self::assertTrue($orderItem->isCancelled());
    }

    public function testChangeQuantityThrowsOnNonPositiveValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The quantity must be positive');

        CustomerOrderItem::createFromProduct(
            customerOrder: $this->stubCustomerOrder(),
            product: $this->stubProduct(),
            quantity: 0,
        );
    }
}
