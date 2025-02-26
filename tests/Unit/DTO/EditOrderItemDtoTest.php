<?php

namespace App\Tests\Unit\DTO;

use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrderItem;
use PHPUnit\Framework\TestCase;

class EditOrderItemDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $orderItemId = 1;
        $quantity = 10;
        $priceIncVat = '100.00';
        $allowCancel = true;

        $dto = new EditOrderItemDto($orderItemId, $quantity, $priceIncVat, $allowCancel);

        $this->assertEquals($orderItemId, $dto->getId());
        $this->assertEquals($quantity, $dto->getQuantity());
        $this->assertEquals($priceIncVat, $dto->getPriceIncVat());
        $this->assertTrue($dto->getAllowCancel());
    }

    public function testSetQuantity(): void
    {
        $dto = new EditOrderItemDto(1, null, '100.00', false);
        $dto->setQuantity(10);

        $this->assertEquals(10, $dto->getQuantity());
    }

    public function testSetPriceIncVat(): void
    {
        $dto = new EditOrderItemDto(1, 10, null, false);
        $dto->setPriceIncVat('120.00');

        $this->assertEquals('120.00', $dto->getPriceIncVat());
    }

    public function testFromEntity(): void
    {
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getId')->willReturn(1);
        $customerOrderItem->method('getQuantity')->willReturn(10);
        $customerOrderItem->method('getPriceIncVat')->willReturn('100.00');
        $customerOrderItem->method('allowCancel')->willReturn(true);

        $dto = EditOrderItemDto::fromEntity($customerOrderItem);

        $this->assertEquals($customerOrderItem->getId(), $dto->getId());
        $this->assertEquals($customerOrderItem->getQuantity(), $dto->getQuantity());
        $this->assertEquals($customerOrderItem->getPriceIncVat(), $dto->getPriceIncVat());
        $this->assertTrue($dto->getAllowCancel());
    }
}