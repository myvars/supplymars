<?php

namespace App\Tests\Integration\Service\OrderProcessing;

use App\Service\OrderProcessing\StatusLogUtility;
use App\Shared\Domain\Event\DomainEventType;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\PurchaseOrderFactory;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use tests\Shared\Factory\StatusChangeLogFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class StatusLogUtilityIntegrationTest extends KernelTestCase
{
    use Factories;

    private StatusLogUtility $statusLogUtility;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->statusLogUtility = new StatusLogUtility($em);
    }

    public function testForCustomerOrder(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();
        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::ORDER_STATUS_CHANGED,
            'eventTypeId' => $customerOrder->getId()
        ]);

        $logs = $this->statusLogUtility->forCustomerOrder($customerOrder);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }

    public function testForCustomerOrderItem(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne();
        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::ORDER_ITEM_STATUS_CHANGED,
            'eventTypeId' => $customerOrderItem->getId()
        ]);

        $logs = $this->statusLogUtility->forCustomerOrderItem($customerOrderItem);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }

    public function testForPurchaseOrder(): void
    {
        $purchaseOrder = PurchaseOrderFactory::createOne();
        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            'eventTypeId' => $purchaseOrder->getId()
        ]);

        $logs = $this->statusLogUtility->forPurchaseOrder($purchaseOrder);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }

    public function testForPurchaseOrderItem(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
            'eventTypeId' => $purchaseOrderItem->getId()
        ]);

        $logs = $this->statusLogUtility->forPurchaseOrderItem($purchaseOrderItem);
        $this->assertCount(1, $logs);
        $this->assertSame($statusChangeLog, $logs[0]);
    }
}
