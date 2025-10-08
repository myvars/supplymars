<?php

namespace App\Tests\Unit\Service\OrderProcessing;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Entity\StatusChangeLog;
use App\Service\OrderProcessing\StatusLogUtility;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class StatusLogUtilityTest extends TestCase
{
    private MockObject $entityManager;

    private StatusLogUtility $statusLogUtility;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->statusLogUtility = new StatusLogUtility($this->entityManager);
    }

    public function testForCustomerOrder(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getId')->willReturn(1);

        $statusChangeLog = $this->createMock(StatusChangeLog::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([$statusChangeLog]);

        $this->entityManager->method('getRepository')->willReturn($repository);

        $logs = $this->statusLogUtility->forCustomerOrder($customerOrder);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }

    public function testForCustomerOrderItem(): void
    {
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getId')->willReturn(1);

        $statusChangeLog = $this->createMock(StatusChangeLog::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([$statusChangeLog]);

        $this->entityManager->method('getRepository')->willReturn($repository);

        $logs = $this->statusLogUtility->forCustomerOrderItem($customerOrderItem);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }

    public function testForPurchaseOrder(): void
    {
        $purchaseOrder = $this->createMock(PurchaseOrder::class);
        $purchaseOrder->method('getId')->willReturn(1);

        $statusChangeLog = $this->createMock(StatusChangeLog::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([$statusChangeLog]);

        $this->entityManager->method('getRepository')->willReturn($repository);

        $logs = $this->statusLogUtility->forPurchaseOrder($purchaseOrder);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }

    public function testForPurchaseOrderItem(): void
    {
        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getId')->willReturn(1);

        $statusChangeLog = $this->createMock(StatusChangeLog::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([$statusChangeLog]);

        $this->entityManager->method('getRepository')->willReturn($repository);

        $logs = $this->statusLogUtility->forPurchaseOrderItem($purchaseOrderItem);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }
}