<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\CustomerOrderItem;
use App\Enum\OrderStatus;
use App\Event\OrderItemStatusChangedEvent;
use App\EventListener\LogOrderItemStatusChange;
use App\Service\OrderProcessing\StatusChangeLogger;
use PHPUnit\Framework\TestCase;

class LogOrderItemStatusChangeTest extends TestCase
{
    public function testOnOrderItemStatusChangeLogsStatusChange(): void
    {
        $statusChangeLoggerMock = $this->createMock(StatusChangeLogger::class);
        $eventMock = $this->createMock(OrderItemStatusChangedEvent::class);
        $customerOrderItemMock = $this->createMock(CustomerOrderItem::class);

        $customerOrderItemMock->method('getId')->willReturn(1);
        $customerOrderItemMock->method('getStatus')->willReturn(OrderStatus::SHIPPED);
        $eventMock->method('getCustomerOrderItem')->willReturn($customerOrderItemMock);

        $statusChangeLoggerMock->expects($this->once())
            ->method('fromStatusChangeEvent')
            ->with($eventMock, 1, 'SHIPPED');

        $listener = new LogOrderItemStatusChange($statusChangeLoggerMock);
        $listener->onOrderItemStatusChange($eventMock);
    }
}