<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\CustomerOrder;
use App\Enum\OrderStatus;
use App\Event\OrderStatusChangedEvent;
use App\EventListener\LogOrderStatusChange;
use App\Service\OrderProcessing\StatusChangeLogger;
use PHPUnit\Framework\TestCase;

class LogOrderStatusChangeTest extends TestCase
{
    public function testOnOrderStatusChangeLogsStatusChange(): void
    {
        $statusChangeLoggerMock = $this->createMock(StatusChangeLogger::class);
        $eventMock = $this->createMock(OrderStatusChangedEvent::class);
        $customerOrderMock = $this->createMock(CustomerOrder::class);

        $customerOrderMock->method('getId')->willReturn(1);
        $customerOrderMock->method('getStatus')->willReturn(OrderStatus::SHIPPED);
        $eventMock->method('getCustomerOrder')->willReturn($customerOrderMock);

        $statusChangeLoggerMock->expects($this->once())
            ->method('fromStatusChangeEvent')
            ->with($eventMock, 1, 'SHIPPED');

        $listener = new LogOrderStatusChange($statusChangeLoggerMock);
        $listener->onOrderStatusChange($eventMock);
    }
}