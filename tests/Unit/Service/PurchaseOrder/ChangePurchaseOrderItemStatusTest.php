<?php

namespace App\Tests\Unit\Service\PurchaseOrder;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Enum\PurchaseOrderStatus;
use App\Repository\PurchaseOrderItemRepository;
use App\Service\Crud\Common\CrudOptions;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ChangePurchaseOrderItemStatusTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private DomainEventDispatcher $domainEventDispatcher;
    private ChangePurchaseOrderItemStatus $changePurchaseOrderItemStatus;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->domainEventDispatcher = $this->createMock(DomainEventDispatcher::class);
        $this->changePurchaseOrderItemStatus = new ChangePurchaseOrderItemStatus($this->entityManager, $this->domainEventDispatcher);
    }

    public function testHandleWithInvalidEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be instance of ChangePurchaseOrderItemStatusDto');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->changePurchaseOrderItemStatus->handle($crudOptions);
    }

    public function testHandleWithoutAllowEdit(): void
    {
        $dto = $this->createMock(ChangePurchaseOrderItemStatusDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getPurchaseOrderItemStatus')->willReturn(PurchaseOrderStatus::PROCESSING);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('allowStatusChange')->willReturn(false);
        $this->entityManager->method('getRepository')->willReturn($this->createMock(PurchaseOrderItemRepository::class));
        $this->entityManager->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Purchase order item cannot be edited');

        $this->changePurchaseOrderItemStatus->handle($crudOptions);
    }

    public function testChangePurchaseOrderItemStatusSuccessfully(): void
    {
        $dto = $this->createMock(ChangePurchaseOrderItemStatusDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getPurchaseOrderItemStatus')->willReturn(PurchaseOrderStatus::PROCESSING);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $purchaseOrder = $this->createMock(PurchaseOrder::class);

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('allowStatusChange')->willReturn(true);
        $purchaseOrderItem->method('getStatus')->willReturn(PurchaseOrderStatus::PENDING);
        $purchaseOrderItem->method('getPurchaseOrder')->willReturn($purchaseOrder);
        $purchaseOrderItem->method('getCustomerOrderItem')->willReturn($customerOrderItem);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);

        $this->entityManager->method('getRepository')->willReturn($this->createMock(PurchaseOrderItemRepository::class));
        $this->entityManager->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $purchaseOrderItem->expects($this->once())->method('updateStatus')->with(PurchaseOrderStatus::PROCESSING);
        $this->entityManager->expects($this->once())->method('persist')->with($purchaseOrderItem);
        $this->entityManager->expects($this->once())->method('flush');
        $this->domainEventDispatcher->expects($this->once())->method('dispatchProviderEvents');

        $this->changePurchaseOrderItemStatus->handle($crudOptions);
    }
}