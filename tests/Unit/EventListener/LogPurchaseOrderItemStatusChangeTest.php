<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\PurchaseOrderItem;
use App\Enum\PurchaseOrderStatus;
use App\Event\PurchaseOrderItemStatusChangedEvent;
use App\EventListener\LogPurchaseOrderItemStatusChange;
use App\Service\OrderProcessing\StatusChangeLogger;
use PHPUnit\Framework\TestCase;

class LogPurchaseOrderItemStatusChangeTest extends TestCase
{
    public function testOnPurchaseOrderItemStatusChangeLogsStatusChange(): void
    {
        $statusChangeLoggerMock = $this->createMock(StatusChangeLogger::class);
        $eventMock = $this->createMock(PurchaseOrderItemStatusChangedEvent::class);
        $purchaseOrderItemMock = $this->createMock(PurchaseOrderItem::class);

        $purchaseOrderItemMock->method('getId')->willReturn(1);
        $purchaseOrderItemMock->method('getStatus')->willReturn(PurchaseOrderStatus::PROCESSING);
        $eventMock->method('getPurchaseOrderItem')->willReturn($purchaseOrderItemMock);

        $statusChangeLoggerMock->expects($this->once())
            ->method('fromStatusChangeEvent')
            ->with($eventMock, 1, 'PROCESSING');

        $listener = new LogPurchaseOrderItemStatusChange($statusChangeLoggerMock);
        $listener->onPurchaseOrderItemStatusChange($eventMock);
    }
}