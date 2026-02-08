<?php

namespace App\Tests\Order\Domain;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\Event\OrderStatusWasChangedEvent;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\ValueObject\ShippingMethod;
use PHPUnit\Framework\TestCase;

class OrderDomainTest extends TestCase
{
    private function stubUser(): User
    {
        $user = $this->createStub(User::class);
        $user->method('getShippingAddress')->willReturn($this->stubAddress());
        $user->method('getBillingAddress')->willReturn($this->stubAddress());

        return $user;
    }

    private function stubVatRate(string $rate = '20.000'): VatRate
    {
        $vatRate = $this->createStub(VatRate::class);
        $vatRate->method('getRate')->willReturn($rate);

        return $vatRate;
    }

    private function stubAddress(): Address
    {
        return $this->createStub(Address::class);
    }

    public function testCreateFromCustomerSetsDefaultStatus(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: 'TEST-001',
        );

        self::assertSame(OrderStatus::PENDING, $order->getStatus());
    }

    public function testCreateFromCustomerSetsShippingPrices(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate('20.000'),
            customerOrderRef: null,
        );

        self::assertSame('3.99', $order->getShippingPrice());
        self::assertSame('4.79', $order->getShippingPriceIncVat());
    }

    public function testCreateFromCustomerSetsDueDate(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        $expectedDueDate = new \DateTimeImmutable('+3 days')->format('Y-m-d');
        self::assertSame($expectedDueDate, $order->getDueDate()->format('Y-m-d'));
    }

    public function testCreateFromCustomerSetsAddressesFromCustomer(): void
    {
        $shippingAddress = $this->stubAddress();
        $billingAddress = $this->stubAddress();

        $customer = $this->createStub(User::class);
        $customer->method('getShippingAddress')->willReturn($shippingAddress);
        $customer->method('getBillingAddress')->willReturn($billingAddress);

        $order = CustomerOrder::createFromCustomer(
            customer: $customer,
            shippingMethod: ShippingMethod::NEXT_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        self::assertSame($shippingAddress, $order->getShippingAddress());
        self::assertSame($billingAddress, $order->getBillingAddress());
    }

    public function testAllowEditReturnsTrueForPendingStatus(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        self::assertSame(OrderStatus::PENDING, $order->getStatus());
        self::assertTrue($order->allowEdit());
    }

    public function testAllowCancelReturnsTrueOnlyForPendingStatus(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        self::assertSame(OrderStatus::PENDING, $order->getStatus());
        self::assertTrue($order->allowCancel());
    }

    public function testCancelOrderWithNoItemsResetsStatus(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        $order->cancelOrder();

        // Orders with no items reset to PENDING via generateStatus()
        self::assertSame(OrderStatus::PENDING, $order->getStatus());
    }

    public function testStatusChangeRaisesEvent(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        $order->releaseDomainEvents();

        $order->cancelOrder();

        $events = $order->releaseDomainEvents();
        $statusEvents = array_filter(
            $events,
            fn (AbstractDomainEvent $event): bool => $event instanceof OrderStatusWasChangedEvent
        );

        self::assertNotEmpty($statusEvents);
    }

    public function testNextDayShippingSetsCorrectPricesAndDueDate(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::NEXT_DAY,
            vatRate: $this->stubVatRate('20.000'),
            customerOrderRef: null,
        );

        self::assertSame('9.99', $order->getShippingPrice());
        self::assertSame('11.99', $order->getShippingPriceIncVat());

        $expectedDueDate = new \DateTimeImmutable('+1 day')->format('Y-m-d');
        self::assertSame($expectedDueDate, $order->getDueDate()->format('Y-m-d'));
    }

    public function testOrderStatusAllowCancelReturnsFalseForNonPendingStatuses(): void
    {
        // Test the OrderStatus enum behavior directly
        self::assertTrue(OrderStatus::PENDING->allowCancel());
        self::assertFalse(OrderStatus::PROCESSING->allowCancel());
        self::assertFalse(OrderStatus::SHIPPED->allowCancel());
        self::assertFalse(OrderStatus::DELIVERED->allowCancel());
        self::assertFalse(OrderStatus::CANCELLED->allowCancel());
    }

    private function stubOrderItem(OrderStatus $status): CustomerOrderItem
    {
        $item = $this->createStub(CustomerOrderItem::class);
        $item->method('getStatus')->willReturn($status);
        $item->method('getTotalPrice')->willReturn('10.00');
        $item->method('getTotalPriceIncVat')->willReturn('12.00');
        $item->method('getTotalWeight')->willReturn(100);

        return $item;
    }

    public function testGenerateStatusUsesLowestLevelFromItems(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        // Add items with different statuses using reflection
        $item1 = $this->stubOrderItem(OrderStatus::SHIPPED); // Level 3
        $item2 = $this->stubOrderItem(OrderStatus::PROCESSING); // Level 2 - lowest

        $reflection = new \ReflectionClass($order);
        $itemsProperty = $reflection->getProperty('customerOrderItems');
        $items = $itemsProperty->getValue($order);
        $items->add($item1);
        $items->add($item2);

        $order->generateStatus();

        // Should use the lowest level status (PROCESSING = 2)
        self::assertSame(OrderStatus::PROCESSING, $order->getStatus());
    }

    public function testGenerateStatusWithAllShippedItemsReturnsShipped(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        $item1 = $this->stubOrderItem(OrderStatus::SHIPPED);
        $item2 = $this->stubOrderItem(OrderStatus::SHIPPED);

        $reflection = new \ReflectionClass($order);
        $itemsProperty = $reflection->getProperty('customerOrderItems');
        $items = $itemsProperty->getValue($order);
        $items->add($item1);
        $items->add($item2);

        $order->generateStatus();

        self::assertSame(OrderStatus::SHIPPED, $order->getStatus());
    }

    public function testGenerateStatusWithMixedDeliveredAndShippedReturnsShipped(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        $item1 = $this->stubOrderItem(OrderStatus::DELIVERED); // Level 4
        $item2 = $this->stubOrderItem(OrderStatus::SHIPPED);   // Level 3 - lowest

        $reflection = new \ReflectionClass($order);
        $itemsProperty = $reflection->getProperty('customerOrderItems');
        $items = $itemsProperty->getValue($order);
        $items->add($item1);
        $items->add($item2);

        $order->generateStatus();

        self::assertSame(OrderStatus::SHIPPED, $order->getStatus());
    }

    public function testGenerateStatusWithCancelledItemReturnsLowestNonCancelledLevel(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        // CANCELLED has level 5 (highest), so if another item is PROCESSING (2), order should be PROCESSING
        $item1 = $this->stubOrderItem(OrderStatus::CANCELLED);   // Level 5
        $item2 = $this->stubOrderItem(OrderStatus::PROCESSING);  // Level 2 - lowest

        $reflection = new \ReflectionClass($order);
        $itemsProperty = $reflection->getProperty('customerOrderItems');
        $items = $itemsProperty->getValue($order);
        $items->add($item1);
        $items->add($item2);

        $order->generateStatus();

        self::assertSame(OrderStatus::PROCESSING, $order->getStatus());
    }

    public function testAllowEditReturnsFalseForShippedStatus(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        // Set status to SHIPPED via reflection
        $reflection = new \ReflectionClass($order);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setValue($order, OrderStatus::SHIPPED);

        self::assertSame(OrderStatus::SHIPPED, $order->getStatus());
        self::assertFalse($order->allowEdit());
    }

    public function testAllowEditReturnsTrueForProcessingStatus(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: null,
        );

        $reflection = new \ReflectionClass($order);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setValue($order, OrderStatus::PROCESSING);

        self::assertSame(OrderStatus::PROCESSING, $order->getStatus());
        self::assertTrue($order->allowEdit());
    }
}
