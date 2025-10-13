<?php

namespace App\Tests\Order\Integration;

use App\Order\Application\DTO\CreateOrderDto;
use App\Shared\Domain\ValueObject\ShippingMethod;
use tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CreateOrderDtoIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCreateOrderDto(): void
    {
        $customer = UserFactory::createOne();

        $dto = new CreateOrderDto();
        $dto->setCustomerId($customer->getId());
        $dto->setShippingMethod(ShippingMethod::NEXT_DAY);
        $dto->setCustomerOrderRef('ORD123');

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testCustomerIdIsRequired(): void
    {
        $dto = new CreateOrderDto();
        $dto->setCustomerId(null);

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a customer Id', $violations[0]->getMessage());
    }

    public function testInvalidCustomerId(): void
    {
        $dto = new CreateOrderDto();
        $dto->setCustomerId(1);

        $violations = $this->validator->validate($dto);
        $this->assertSame('Customer with Id "1" not found.', $violations[0]->getMessage());
    }

    public function testShippingMethodIsRequired(): void
    {
        $customer = UserFactory::createOne();

        $dto = new CreateOrderDto();
        $dto->setCustomerId($customer->getId());
        $dto->setShippingMethod(null);

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a shipping method', $violations[0]->getMessage());
    }
}
