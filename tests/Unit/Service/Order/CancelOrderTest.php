<?php

namespace App\Tests\Unit\Service\Order;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Enum\OrderStatus;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\CancelOrder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CancelOrderTest extends TestCase
{
    private MockObject $entityManager;

    private CancelOrder $cancelOrder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cancelOrder = new CancelOrder($this->entityManager);
    }

    public function testHandleWithNonCustomerOrderEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be instance of CustomerOrder');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->cancelOrder->handle($crudOptions);
    }

    public function testHandleWithAlreadyCancelledOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order is already cancelled');

        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getStatus')->willReturn(OrderStatus::CANCELLED);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrder);

        $this->cancelOrder->handle($crudOptions);
    }

    public function testHandleWithNonCancellableOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order cannot be cancelled');

        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getStatus')->willReturn(OrderStatus::PROCESSING);
        $customerOrder->method('allowCancel')->willReturn(false);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrder);

        $this->cancelOrder->handle($crudOptions);
    }

    public function testCancelOrderWhenAllowed(): void
    {
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->expects($this->once())->method('cancelItem');

        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getStatus')
            ->willReturnOnConsecutiveCalls(OrderStatus::PENDING, OrderStatus::CANCELLED);
        $customerOrder->method('allowCancel')->willReturn(true);
        $customerOrder->method('getCustomerOrderItems')
            ->willReturn(new ArrayCollection([$customerOrderItem]));
        $customerOrder->expects($this->once())->method('cancelOrder');
        $customerOrder->expects($this->once())->method('generateStatus');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrder);

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->cancelOrder->handle($crudOptions);

        $this->assertSame(OrderStatus::CANCELLED, $customerOrder->getStatus());
    }
}
