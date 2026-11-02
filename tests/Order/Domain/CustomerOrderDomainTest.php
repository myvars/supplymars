<?php

declare(strict_types=1);

namespace App\Tests\Order\Domain;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\Event\OrderLockWasChangedEvent;
use App\Order\Domain\Model\Order\Event\OrderStatusWasChangedEvent;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\Domain\Model\Order\ShippingMethod;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Shared\Domain\Event\AbstractDomainEvent;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class CustomerOrderDomainTest extends TestCase
{
    private function stubUser(): User
    {
        $user = $this->createStub(User::class);
        $user->method('getShippingAddress')->willReturn($this->stubAddress());
        $user->method('getBillingAddress')->willReturn($this->stubAddress());

        return $user;
    }

    /** @param numeric-string $rate */
    private function vatRate(string $rate = '20.000'): VatRate
    {
        return VatRate::create(name: 'Standard', rate: $rate);
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
            vatRate: $this->vatRate(),
            customerOrderRef: 'TEST-001',
        );

        self::assertSame(OrderStatus::PENDING, $order->getStatus());
    }

    public function testCreateFromCustomerSetsShippingPrices(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->vatRate('20.000'),
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
            vatRate: $this->vatRate(),
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
            vatRate: $this->vatRate(),
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
            vatRate: $this->vatRate(),
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
            vatRate: $this->vatRate(),
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
            vatRate: $this->vatRate(),
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
            vatRate: $this->vatRate(),
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

    public function testLockingRaisesAnEventWithWhoHoldsItAndWhoActed(): void
    {
        $order = $this->order();
        $sam = $this->stubStaff();

        $order->lockOrder($sam, $sam);

        $event = $this->lockEvent($order);
        self::assertTrue($event->getId()->equals($order->getPublicId()));
        self::assertNull($event->getPreviousHolderId());
        self::assertTrue($event->getHolderId()?->equals($sam->getPublicId()));
        self::assertTrue($event->getChangedById()->equals($sam->getPublicId()));
        self::assertFalse($event->isReleasedByAnotherUser());
    }

    public function testReleasingAnotherUsersLockIsMarkedAsSuch(): void
    {
        $order = $this->order();
        $holder = $this->stubStaff();
        $releaser = $this->stubStaff();
        $order->lockOrder($holder, $holder);
        $order->releaseDomainEvents();

        $order->lockOrder(null, $releaser);

        $event = $this->lockEvent($order);
        self::assertNull($order->getOrderLock());
        self::assertTrue($event->getPreviousHolderId()?->equals($holder->getPublicId()));
        self::assertNull($event->getHolderId());
        self::assertTrue($event->isReleasedByAnotherUser());
    }

    public function testTakingALockFromItsHolderIsMarkedAsReleased(): void
    {
        $order = $this->order();
        $holder = $this->stubStaff();
        $other = $this->stubStaff();
        $order->lockOrder($holder, $holder);
        $order->releaseDomainEvents();

        $order->lockOrder($other, $other);

        self::assertTrue($this->lockEvent($order)->isReleasedByAnotherUser());
    }

    public function testReleasingYourOwnLockIsNotMarkedAsReleasedByAnother(): void
    {
        $order = $this->order();
        $holder = $this->stubStaff();
        $order->lockOrder($holder, $holder);
        $order->releaseDomainEvents();

        $order->lockOrder(null, $holder);

        self::assertFalse($this->lockEvent($order)->isReleasedByAnotherUser());
    }

    public function testLockingToTheCurrentHolderRaisesNothing(): void
    {
        $order = $this->order();
        $holder = $this->stubStaff();
        $order->lockOrder($holder, $holder);
        $order->releaseDomainEvents();

        $order->lockOrder($holder, $holder);
        $order->lockOrder(null, $holder);
        $order->releaseDomainEvents();
        $order->lockOrder(null, $holder);

        self::assertSame([], $order->releaseDomainEvents());
    }

    public function testNextDayShippingSetsCorrectPricesAndDueDate(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::NEXT_DAY,
            vatRate: $this->vatRate('20.000'),
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

    /**
     * The order takes the lowest-level status of its items: CANCELLED is the highest level, so it never wins
     * over a live item.
     */
    #[TestWith([OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::PROCESSING], 'lowest level first')]
    #[TestWith([OrderStatus::SHIPPED, OrderStatus::PROCESSING, OrderStatus::PROCESSING], 'lowest level last')]
    #[TestWith([OrderStatus::SHIPPED, OrderStatus::SHIPPED, OrderStatus::SHIPPED], 'all items shipped')]
    #[TestWith([OrderStatus::DELIVERED, OrderStatus::SHIPPED, OrderStatus::SHIPPED], 'delivered and shipped')]
    #[TestWith([OrderStatus::PROCESSING, OrderStatus::CANCELLED, OrderStatus::PROCESSING], 'cancelled ranks above a live item')]
    public function testGenerateStatusUsesLowestLevelFromItems(
        OrderStatus $first,
        OrderStatus $second,
        OrderStatus $expected,
    ): void {
        $order = $this->order();
        $order->addCustomerOrderItem($this->stubOrderItem($first));
        $order->addCustomerOrderItem($this->stubOrderItem($second));

        $order->generateStatus();

        self::assertSame($expected, $order->getStatus());
    }

    #[TestWith([OrderStatus::PROCESSING, true], 'processing')]
    #[TestWith([OrderStatus::SHIPPED, false], 'shipped')]
    public function testAllowEditFollowsTheStatusTheItemsGiveTheOrder(OrderStatus $itemStatus, bool $allowEdit): void
    {
        $order = $this->order();
        $order->addCustomerOrderItem($this->stubOrderItem($itemStatus));
        $order->generateStatus();

        self::assertSame($itemStatus, $order->getStatus());
        self::assertSame($allowEdit, $order->allowEdit());
    }

    private function order(): CustomerOrder
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->vatRate(),
            customerOrderRef: null,
        );
        $order->releaseDomainEvents();

        return $order;
    }

    private function stubStaff(): User
    {
        $user = $this->createStub(User::class);
        $user->method('getPublicId')->willReturn(UserPublicId::new());

        return $user;
    }

    private function lockEvent(CustomerOrder $order): OrderLockWasChangedEvent
    {
        $events = $order->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderLockWasChangedEvent::class, $events[0]);

        return $events[0];
    }
}
