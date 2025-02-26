<?php

namespace App\Tests\Unit\DTO;

use App\DTO\CreateOrderItemDto;
use App\Entity\CustomerOrder;
use PHPUnit\Framework\TestCase;

class CreateOrderItemDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $orderId = 1;
        $productId = 100;
        $quantity = 10;

        $dto = new CreateOrderItemDto($orderId, $productId, $quantity);

        $this->assertEquals($orderId, $dto->getId());
        $this->assertEquals($productId, $dto->getProductId());
        $this->assertEquals($quantity, $dto->getQuantity());
    }

    public function testSetProductId(): void
    {
        $dto = new CreateOrderItemDto(1, null, 10);
        $dto->setProductId(100);

        $this->assertEquals(100, $dto->getProductId());
    }

    public function testSetQuantity(): void
    {
        $dto = new CreateOrderItemDto(1, 100, null);
        $dto->setQuantity(10);

        $this->assertEquals(10, $dto->getQuantity());
    }

    public function testFromEntity(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getId')->willReturn(1);

        $dto = CreateOrderItemDto::fromEntity($customerOrder);

        $this->assertEquals($customerOrder->getId(), $dto->getId());
        $this->assertNull($dto->getProductId());
        $this->assertEquals(1, $dto->getQuantity());
    }
}