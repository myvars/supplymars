<?php

namespace App\Tests\Order\Domain;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\Event\OrderStatusWasChangedEvent;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Pricing\Domain\Model\VatRate\VatRate;
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
            fn ($event): bool => $event instanceof OrderStatusWasChangedEvent
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
}
