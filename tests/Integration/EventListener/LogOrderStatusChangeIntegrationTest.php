<?php

namespace App\Tests\Integration\EventListener;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Enum\DomainEventType;
use App\Enum\OrderStatus;
use App\Enum\PurchaseOrderStatus;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\StatusChangeLogFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\OrderProcessing\StatusChangeLogger;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\Utility\DomainEventDispatcher;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class LogOrderStatusChangeIntegrationTest extends KernelTestCase
{
    use Factories;

    private ChangePurchaseOrderItemStatus $changePurchaseOrderItemStatus;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $domainEventDispatcher = static::getContainer()->get(DomainEventDispatcher::class);
        $this->changePurchaseOrderItemStatus = new ChangePurchaseOrderItemStatus($entityManager, $domainEventDispatcher);
        StaffUserStory::load();
    }

    public function testOnOrderItemStatusChangeLogsStatusChange(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $dto = new ChangePurchaseOrderItemStatusDto(
            $purchaseOrderItem->getId(),
            PurchaseOrderStatus::PROCESSING
        );

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->changePurchaseOrderItemStatus->handle($crudOptions);

        $orderStatusChangeLog = StatusChangeLogFactory::repository()->findOneBy([
            'eventTypeId' => $purchaseOrderItem->getCustomerOrderItem()->getCustomerOrder()->getId(),
            'eventType' => DomainEventType::ORDER_STATUS_CHANGED,
            'status' => OrderStatus::PROCESSING

        ]);

        $this->assertNotNull($orderStatusChangeLog);
    }
}