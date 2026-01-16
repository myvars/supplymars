<?php

namespace App\Tests\Integration\Service\PurchaseOrder;

use App\Purchasing\Application\DTO\ChangePurchaseOrderItemStatusDto;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Service\Crud\Common\CrudContext;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use Doctrine\ORM\EntityManagerInterface;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use Zenstruck\Foundry\Test\Factories;

class ChangePurchaseOrderItemStatusIntegrationTest extends KernelTestCase
{
    use Factories;

    private ChangePurchaseOrderItemStatus $changePurchaseOrderItemStatus;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->changePurchaseOrderItemStatus = new ChangePurchaseOrderItemStatus($em);
        StaffUserStory::load();
    }

    public function testHandleWithValidChangePurchaseOrderItemStatusDto(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $dto = new ChangePurchaseOrderItemStatusDto(
            $purchaseOrderItem->getId(),
            PurchaseOrderStatus::PROCESSING
        );

        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->changePurchaseOrderItemStatus)($context);

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

        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->changePurchaseOrderItemStatus)($context);

        $updatedPurchaseOrderItem = PurchaseOrderItemFactory::repository()->find($purchaseOrderItem->getId());

        $this->assertInstanceOf(PurchaseOrderItem::class, $updatedPurchaseOrderItem);
        $this->assertSame(PurchaseOrderStatus::PENDING, $updatedPurchaseOrderItem->getStatus());
    }
}
