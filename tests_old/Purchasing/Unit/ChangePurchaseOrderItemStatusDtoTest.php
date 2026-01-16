<?php

namespace App\Tests\Purchasing\Unit;

use App\Purchasing\Application\DTO\ChangePurchaseOrderItemStatusDto;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use PHPUnit\Framework\TestCase;

class ChangePurchaseOrderItemStatusDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $purchaseOrderItemId = 1;

        $dto = new ChangePurchaseOrderItemStatusDto(
            $purchaseOrderItemId,
            PurchaseOrderStatus::PROCESSING
        );

        $this->assertEquals($purchaseOrderItemId, $dto->getId());
        $this->assertSame(PurchaseOrderStatus::PROCESSING, $dto->getPurchaseOrderItemStatus());
    }

    public function testSetPurchaseOrderItemStatus(): void
    {
        $dto = new ChangePurchaseOrderItemStatusDto(1, null);

        $dto->setPurchaseOrderItemStatus(PurchaseOrderStatus::PROCESSING);

        $this->assertSame(PurchaseOrderStatus::PROCESSING, $dto->getPurchaseOrderItemStatus());
    }

    public function testFromEntity(): void
    {
        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getId')->willReturn(1);
        $purchaseOrderItem->method('getStatus')->willReturn(PurchaseOrderStatus::PROCESSING);

        $dto = ChangePurchaseOrderItemStatusDto::fromEntity($purchaseOrderItem);

        $this->assertEquals($purchaseOrderItem->getId(), $dto->getId());
        $this->assertSame($purchaseOrderItem->getStatus(), $dto->getPurchaseOrderItemStatus());
    }
}
