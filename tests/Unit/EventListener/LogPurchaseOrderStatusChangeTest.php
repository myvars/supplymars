<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\PurchaseOrder;
use App\Enum\PurchaseOrderStatus;
use App\Event\PurchaseOrderStatusChangedEvent;
use App\EventListener\LogPurchaseOrderStatusChange;
use App\Service\OrderProcessing\StatusChangeLogger;
use PHPUnit\Framework\TestCase;

class LogPurchaseOrderStatusChangeTest extends TestCase
{
    public function testOnPurchaseOrderStatusChangeLogsStatusChange(): void
    {
        $statusChangeLoggerMock = $this->createMock(StatusChangeLogger::class);
        $eventMock = $this->createMock(PurchaseOrderStatusChangedEvent::class);
        $purchaseOrderMock = $this->createMock(PurchaseOrder::class);

        $purchaseOrderMock->method('getId')->willReturn(1);
        $purchaseOrderMock->method('getStatus')->willReturn(PurchaseOrderStatus::PROCESSING);
        $eventMock->method('getPurchaseOrder')->willReturn($purchaseOrderMock);

        $statusChangeLoggerMock->expects($this->once())
            ->method('fromStatusChangeEvent')
            ->with($eventMock, 1, 'PROCESSING');

        $listener = new LogPurchaseOrderStatusChange($statusChangeLoggerMock);
        $listener->onPurchaseOrderStatusChange($eventMock);
    }
}