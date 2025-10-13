<?php

namespace App\Tests\Purchasing\Unit;

use App\Purchasing\Application\DTO\EditPurchaseOrderItemDto;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use PHPUnit\Framework\TestCase;

class EditPurchaseOrderItemDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $purchaseOrderItemId = 1;
        $quantity = 10;

        $dto = new EditPurchaseOrderItemDto($purchaseOrderItemId, $quantity);

        $this->assertEquals($purchaseOrderItemId, $dto->getId());
        $this->assertEquals($quantity, $dto->getQuantity());
    }

    public function testSetQuantity(): void
    {
        $dto = new EditPurchaseOrderItemDto(1, null);
        $dto->setQuantity(10);

        $this->assertEquals(10, $dto->getQuantity());
    }

    public function testFromEntity(): void
    {
        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getId')->willReturn(1);
        $purchaseOrderItem->method('getQuantity')->willReturn(10);

        $dto = EditPurchaseOrderItemDto::fromEntity($purchaseOrderItem);

        $this->assertEquals($purchaseOrderItem->getId(), $dto->getId());
        $this->assertEquals($purchaseOrderItem->getQuantity(), $dto->getQuantity());
    }
}
