<?php

namespace App\Tests\Unit\Service\Order;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Enum\OrderStatus;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\CancelOrderItem;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CancelOrderItemTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private DomainEventDispatcher $domainEventDispatcher;
    private CancelOrderItem $cancelOrderItem;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->domainEventDispatcher = $this->createMock(DomainEventDispatcher::class);
        $this->cancelOrderItem = new CancelOrderItem($this->entityManager, $this->domainEventDispatcher);
    }

    public function testHandleWithNonCustomerOrderItemEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of CustomerOrderItem');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->cancelOrderItem->handle($crudOptions);
    }

    public function testHandleWithAlreadyCancelledOrderItem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order item is already cancelled');

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getStatus')->willReturn(OrderStatus::CANCELLED);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrderItem);

        $this->cancelOrderItem->handle($crudOptions);
    }

    public function testHandleWithNonCancellableOrderItem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order item cannot be cancelled');

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getStatus')->willReturn(OrderStatus::SHIPPED);
        $customerOrderItem->method('allowCancel')->willReturn(false);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrderItem);

        $this->cancelOrderItem->handle($crudOptions);
    }

    public function testCancelOrderItemWhenAllowed()
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getStatus')
            ->willReturnOnConsecutiveCalls(OrderStatus::PENDING, OrderStatus::CANCELLED);
        $customerOrderItem->method('allowCancel')->willReturn(true);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);
        $customerOrderItem->expects($this->once())->method('cancelItem');
        $customerOrder->expects($this->once())->method('generateStatus');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrderItem);

        $this->entityManager->expects($this->once())->method('persist')->with($customerOrder);
        $this->entityManager->expects($this->once())->method('flush');
        $this->domainEventDispatcher->expects($this->once())->method('dispatchProviderEvents')->with([$customerOrderItem, $customerOrder]);

        $this->cancelOrderItem->handle($crudOptions);

        $this->assertSame(OrderStatus::CANCELLED, $customerOrderItem->getStatus());
    }
}