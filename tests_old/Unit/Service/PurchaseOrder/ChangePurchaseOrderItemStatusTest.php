<?php

namespace App\Tests\Unit\Service\PurchaseOrder;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Application\DTO\ChangePurchaseOrderItemStatusDto;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderItemDoctrineRepository;
use App\Service\Crud\Common\CrudContext;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangePurchaseOrderItemStatusTest extends TestCase
{
    private MockObject $em;

    private ChangePurchaseOrderItemStatus $changePurchaseOrderItemStatus;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->changePurchaseOrderItemStatus = new ChangePurchaseOrderItemStatus($this->em);
    }

    public function testHandleWithInvalidEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be instance of ChangePurchaseOrderItemStatusDto');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->changePurchaseOrderItemStatus)($context);
    }

    public function testHandleWithoutAllowEdit(): void
    {
        $dto = $this->createMock(ChangePurchaseOrderItemStatusDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getPurchaseOrderItemStatus')->willReturn(PurchaseOrderStatus::PROCESSING);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('allowStatusChange')->willReturn(false);
        $this->em->method('getRepository')->willReturn($this->createMock(PurchaseOrderItemDoctrineRepository::class));
        $this->em->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Purchase order item cannot be edited');

        ($this->changePurchaseOrderItemStatus)($context);
    }

    public function testChangePurchaseOrderItemStatusSuccessfully(): void
    {
        $dto = $this->createMock(ChangePurchaseOrderItemStatusDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getPurchaseOrderItemStatus')->willReturn(PurchaseOrderStatus::PROCESSING);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $purchaseOrder = $this->createMock(PurchaseOrder::class);

        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('allowStatusChange')->willReturn(true);
        $purchaseOrderItem->method('getStatus')->willReturn(PurchaseOrderStatus::PENDING);
        $purchaseOrderItem->method('getPurchaseOrder')->willReturn($purchaseOrder);
        $purchaseOrderItem->method('getCustomerOrderItem')->willReturn($customerOrderItem);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);

        $this->em->method('getRepository')->willReturn($this->createMock(PurchaseOrderItemDoctrineRepository::class));
        $this->em->getRepository(PurchaseOrderItem::class)->method('find')->willReturn($purchaseOrderItem);

        $purchaseOrderItem->expects($this->once())->method('updateStatus')->with(PurchaseOrderStatus::PROCESSING);
        $this->em->expects($this->once())->method('persist')->with($purchaseOrderItem);
        $this->em->expects($this->once())->method('flush');

        ($this->changePurchaseOrderItemStatus)($context);
    }
}
