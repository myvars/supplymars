<?php

namespace App\Tests\Unit\Service\PurchaseOrder;

use PHPUnit\Framework\MockObject\MockObject;
use App\DTO\EditPurchaseOrderItemDto;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Repository\PurchaseOrderItemRepository;
use App\Service\Crud\Common\CrudOptions;
use App\Service\PurchaseOrder\EditPurchaseOrderItem;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class EditPurchaseOrderItemTest extends TestCase
{
    private MockObject $entityManager;

    private EditPurchaseOrderItem $editPurchaseOrderItem;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->editPurchaseOrderItem = new EditPurchaseOrderItem($this->entityManager);
    }

    public function testHandleWithNonEditPurchaseOrderItemDtoEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of EditPurchaseOrderItemDto');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->editPurchaseOrderItem->handle($crudOptions);
    }

    public function testHandleWithMissingPurchaseOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Purchase order item not found');

        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $this->entityManager->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemRepository::class)]
        ]);
        $this->entityManager->getRepository(PurchaseOrderItem::class)->method('find')->willReturn(null);

        $this->editPurchaseOrderItem->handle($crudOptions);
    }

    public function testHandleWithoutAllowEdit(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Purchase order item cannot be edited');

        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('allowEdit')->willReturn(false);

        $this->entityManager->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemRepository::class)]
        ]);
        $this->entityManager->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $this->editPurchaseOrderItem->handle($crudOptions);
    }

    public function testHandleAllowEditSkipsWithSameQuantity(): void
    {
        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(1);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getQuantity')->willReturn(1);
        $purchaseOrderItem->method('allowEdit')->willReturn(true);

        $this->entityManager->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemRepository::class)]
        ]);
        $this->entityManager->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $this->editPurchaseOrderItem->handle($crudOptions);

        $this->entityManager->expects($this->never())->method('flush');
    }

    public function testEditPurchaseOrderWithZeroQuantity(): void
    {
        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(0);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);

        $purchaseOrder = $this->createMock(PurchaseOrder::class);
        $purchaseOrder->method('removePurchaseOrderItem')->with($purchaseOrderItem);
        $purchaseOrder->method('getPurchaseOrderItems')->willReturn(new ArrayCollection([]));

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('removePurchaseOrderItem')->with($purchaseOrderItem);

        $purchaseOrderItem->method('getQuantity')->willReturn(3);
        $purchaseOrderItem->method('allowEdit')->willReturn(true);
        $purchaseOrderItem->method('getCustomerOrderItem')->willReturn($customerOrderItem);
        $purchaseOrderItem->method('getPurchaseOrder')->willReturn($purchaseOrder);

        $this->entityManager->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemRepository::class)]
        ]);
        $this->entityManager->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->exactly(2))->method('remove');
        $this->entityManager->expects($this->once())->method('flush');

        $this->editPurchaseOrderItem->handle($crudOptions);
    }

    public function testEditPurchaseOrderItemSuccessfully(): void
    {
        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(5);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $purchaseOrder = $this->createMock(PurchaseOrder::class);
        $purchaseOrder->expects($this->once())->method('recalculateTotal');

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getQuantity')->willReturn(3);
        $purchaseOrderItem->method('allowEdit')->willReturn(true);
        $purchaseOrderItem->method('getCustomerOrderItem')->willReturn($this->createMock(CustomerOrderItem::class));
        $purchaseOrderItem->method('getPurchaseOrder')->willReturn($purchaseOrder);
        $purchaseOrderItem->expects($this->once())->method('updateItem')->with(5);

        $this->entityManager->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemRepository::class)]
        ]);
        $this->entityManager->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->editPurchaseOrderItem->handle($crudOptions);
    }
}
