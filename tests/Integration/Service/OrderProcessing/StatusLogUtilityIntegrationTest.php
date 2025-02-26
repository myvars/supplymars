<?php

namespace App\Tests\Integration\Service\OrderProcessing;

use App\Enum\DomainEventType;
use App\Factory\CustomerOrderFactory;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\PurchaseOrderFactory;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\StatusChangeLogFactory;
use App\Service\OrderProcessing\StatusLogUtility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class StatusLogUtilityIntegrationTest extends KernelTestCase
{
    use Factories;

    private StatusLogUtility $statusLogUtility;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->statusLogUtility = new StatusLogUtility($entityManager);
    }

    public function testForCustomerOrder(): void
    {
        $customerOrder = CustomerOrderFactory::createOne()->_real();
        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::ORDER_STATUS_CHANGED,
            'eventTypeId' => $customerOrder->getId()
        ])->_real();

        $logs = $this->statusLogUtility->forCustomerOrder($customerOrder);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }

    public function testForCustomerOrderItem(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne()->_real();
        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::ORDER_ITEM_STATUS_CHANGED,
            'eventTypeId' => $customerOrderItem->getId()
        ])->_real();

        $logs = $this->statusLogUtility->forCustomerOrderItem($customerOrderItem);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }

    public function testForPurchaseOrder(): void
    {
        $purchaseOrder = PurchaseOrderFactory::createOne()->_real();
        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            'eventTypeId' => $purchaseOrder->getId()
        ])->_real();

        $logs = $this->statusLogUtility->forPurchaseOrder($purchaseOrder);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }

    public function testForPurchaseOrderItem(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne()->_real();
        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
            'eventTypeId' => $purchaseOrderItem->getId()
        ])->_real();

        $logs = $this->statusLogUtility->forPurchaseOrderItem($purchaseOrderItem);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }
}