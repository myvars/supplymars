<?php

namespace App\Tests\Unit\DTO;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\PurchaseOrderItem;
use App\Enum\PurchaseOrderStatus;
use PHPUnit\Framework\TestCase;

class ChangePurchaseOrderItemStatusDtoTest extends TestCase
{
    public function testFromEntityCreatesDtoWithCorrectValues(): void
    {
        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getId')->willReturn(1);
        $purchaseOrderItem->method('getStatus')->willReturn(PurchaseOrderStatus::ACCEPTED);

        $dto = ChangePurchaseOrderItemStatusDto::fromEntity($purchaseOrderItem);

        $this->assertSame(1, $dto->getId());
        $this->assertSame(PurchaseOrderStatus::ACCEPTED, $dto->getPurchaseOrderItemStatus());
    }

    public function testPurchaseOrderItemIdIsMissing(): void
    {
        $this->expectException(\TypeError::class);
        $dto = new ChangePurchaseOrderItemStatusDto(null, PurchaseOrderStatus::ACCEPTED);
    }

    public function testGetIdReturnsCorrectId(): void
    {
        $dto = new ChangePurchaseOrderItemStatusDto(1, PurchaseOrderStatus::ACCEPTED);
        $this->assertSame(1, $dto->getId());
    }

    public function testGetPurchaseOrderItemStatusReturnsCorrectStatus(): void
    {
        $dto = new ChangePurchaseOrderItemStatusDto(1, PurchaseOrderStatus::ACCEPTED);
        $this->assertSame(PurchaseOrderStatus::ACCEPTED, $dto->getPurchaseOrderItemStatus());
    }
}
