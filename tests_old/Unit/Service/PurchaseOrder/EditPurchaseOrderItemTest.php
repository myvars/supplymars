<?php

namespace App\Tests\Unit\Service\PurchaseOrder;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Application\DTO\EditPurchaseOrderItemDto;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderItemDoctrineRepository;
use App\Service\Crud\Common\CrudContext;
use App\Service\PurchaseOrder\EditPurchaseOrderItem;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EditPurchaseOrderItemTest extends TestCase
{
    private MockObject $em;

    private EditPurchaseOrderItem $editPurchaseOrderItem;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->editPurchaseOrderItem = new EditPurchaseOrderItem($this->em);
    }

    public function testHandleWithNonEditPurchaseOrderItemDtoEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of EditPurchaseOrderItemDto');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->editPurchaseOrderItem)($context);
    }

    public function testHandleWithMissingPurchaseOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Purchase order item not found');

        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $this->em->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemDoctrineRepository::class)],
        ]);
        $this->em->getRepository(PurchaseOrderItem::class)->method('find')->willReturn(null);

        ($this->editPurchaseOrderItem)($context);
    }

    public function testHandleWithoutAllowEdit(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Purchase order item cannot be edited');

        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('allowEdit')->willReturn(false);

        $this->em->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemDoctrineRepository::class)],
        ]);
        $this->em->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        ($this->editPurchaseOrderItem)($context);
    }

    public function testHandleAllowEditSkipsWithSameQuantity(): void
    {
        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(1);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getQuantity')->willReturn(1);
        $purchaseOrderItem->method('allowEdit')->willReturn(true);

        $this->em->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemDoctrineRepository::class)],
        ]);
        $this->em->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        ($this->editPurchaseOrderItem)($context);

        $this->em->expects($this->never())->method('flush');
    }

    public function testEditPurchaseOrderWithZeroQuantity(): void
    {
        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(0);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

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

        $this->em->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemDoctrineRepository::class)],
        ]);
        $this->em->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->exactly(2))->method('remove');
        $this->em->expects($this->once())->method('flush');

        ($this->editPurchaseOrderItem)($context);
    }

    public function testEditPurchaseOrderItemSuccessfully(): void
    {
        $dto = $this->createMock(EditPurchaseOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(5);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $purchaseOrder = $this->createMock(PurchaseOrder::class);
        $purchaseOrder->expects($this->once())->method('recalculateTotal');

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getQuantity')->willReturn(3);
        $purchaseOrderItem->method('allowEdit')->willReturn(true);
        $purchaseOrderItem->method('getCustomerOrderItem')->willReturn($this->createMock(CustomerOrderItem::class));
        $purchaseOrderItem->method('getPurchaseOrder')->willReturn($purchaseOrder);
        $purchaseOrderItem->expects($this->once())->method('updateItemQuantity')->with(5);

        $this->em->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemDoctrineRepository::class)],
        ]);
        $this->em->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        ($this->editPurchaseOrderItem)($context);
    }
}
