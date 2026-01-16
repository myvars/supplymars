<?php

namespace App\Tests\Unit\Service\Order;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\CancelOrderItem;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CancelOrderItemTest extends TestCase
{
    private MockObject $em;

    private CancelOrderItem $cancelOrderItem;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->cancelOrderItem = new CancelOrderItem($this->em);
    }

    public function testHandleWithNonCustomerOrderItemEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of CustomerOrderItem');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->cancelOrderItem)($context);
    }

    public function testHandleWithAlreadyCancelledOrderItem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order item is already cancelled');

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getStatus')->willReturn(OrderStatus::CANCELLED);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrderItem);

        ($this->cancelOrderItem)($context);
    }

    public function testHandleWithNonCancellableOrderItem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order item cannot be cancelled');

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getStatus')->willReturn(OrderStatus::SHIPPED);
        $customerOrderItem->method('allowCancel')->willReturn(false);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrderItem);

        ($this->cancelOrderItem)($context);
    }

    public function testCancelOrderItemWhenAllowed(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getStatus')
            ->willReturnOnConsecutiveCalls(OrderStatus::PENDING, OrderStatus::CANCELLED);
        $customerOrderItem->method('allowCancel')->willReturn(true);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);
        $customerOrderItem->expects($this->once())->method('cancelItem');
        $customerOrder->expects($this->once())->method('generateStatus');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrderItem);

        $this->em->expects($this->once())->method('persist')->with($customerOrder);
        $this->em->expects($this->once())->method('flush');

        ($this->cancelOrderItem)($context);

        $this->assertSame(OrderStatus::CANCELLED, $customerOrderItem->getStatus());
    }
}
