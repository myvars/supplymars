<?php

namespace App\Tests\Order\Unit;

use App\Order\Application\DTO\CreateOrderDto;
use App\Shared\Domain\ValueObject\ShippingMethod;
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
