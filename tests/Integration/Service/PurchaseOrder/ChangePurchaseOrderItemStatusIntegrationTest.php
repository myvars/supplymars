<?php

namespace App\Tests\Integration\Service\PurchaseOrder;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\PurchaseOrderItem;
use App\Enum\PurchaseOrderStatus;
use App\Factory\PurchaseOrderItemFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\Utility\DomainEventDispatcher;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ChangePurchaseOrderItemStatusIntegrationTest extends KernelTestCase
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

    public function testHandleWithValidChangePurchaseOrderItemStatusDto(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $dto = new ChangePurchaseOrderItemStatusDto(
            $purchaseOrderItem->getId(),
            PurchaseOrderStatus::PROCESSING
        );

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->changePurchaseOrderItemStatus->handle($crudOptions);

        $updatedPurchaseOrderItem = PurchaseOrderItemFactory::repository()->find($purchaseOrderItem->getId());

        $this->assertInstanceOf(PurchaseOrderItem::class, $updatedPurchaseOrderItem);
        $this->assertSame(PurchaseOrderStatus::PROCESSING, $updatedPurchaseOrderItem->getStatus());
    }

    public function testHandleWithSameStatusChange(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $dto = new ChangePurchaseOrderItemStatusDto(
            $purchaseOrderItem->getId(),
            PurchaseOrderStatus::PENDING
        );

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->changePurchaseOrderItemStatus->handle($crudOptions);

        $updatedPurchaseOrderItem = PurchaseOrderItemFactory::repository()->find($purchaseOrderItem->getId());

        $this->assertInstanceOf(PurchaseOrderItem::class, $updatedPurchaseOrderItem);
        $this->assertSame(PurchaseOrderStatus::PENDING, $updatedPurchaseOrderItem->getStatus());
    }
}