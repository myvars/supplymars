<?php

namespace App\Tests\Unit\DTO;

use App\DTO\CreateOrderDto;
use App\Enum\ShippingMethod;
use PHPUnit\Framework\TestCase;

class CreateOrderDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new CreateOrderDto();
        $dto->setCustomerId(1);
        $dto->setShippingMethod(ShippingMethod::NEXT_DAY);
        $dto->setCustomerOrderRef('ORD123');

        $this->assertEquals(1, $dto->getCustomerId());
        $this->assertSame(ShippingMethod::NEXT_DAY, $dto->getShippingMethod());
        $this->assertSame('ORD123', $dto->getCustomerOrderRef());
    }
}