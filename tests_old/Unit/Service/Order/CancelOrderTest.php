<?php

namespace App\Tests\Unit\Service\Order;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\CancelOrder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CancelOrderTest extends TestCase
{
    private MockObject $em;

    private CancelOrder $cancelOrder;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->cancelOrder = new CancelOrder($this->em);
    }

    public function testHandleWithNonCustomerOrderEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be instance of CustomerOrder');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->cancelOrder)($context);
    }

    public function testHandleWithAlreadyCancelledOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order is already cancelled');

        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getStatus')->willReturn(OrderStatus::CANCELLED);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrder);

        ($this->cancelOrder)($context);
    }

    public function testHandleWithNonCancellableOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order cannot be cancelled');

        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getStatus')->willReturn(OrderStatus::PROCESSING);
        $customerOrder->method('allowCancel')->willReturn(false);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrder);

        ($this->cancelOrder)($context);
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

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrder);

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');

        ($this->cancelOrder)($context);

        $this->assertSame(OrderStatus::CANCELLED, $customerOrder->getStatus());
    }
}
