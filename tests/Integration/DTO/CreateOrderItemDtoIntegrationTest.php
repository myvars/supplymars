<?php

namespace App\Tests\Integration\DTO;

use App\DTO\CreateOrderItemDto;
use App\Factory\CustomerOrderFactory;
use App\Factory\ProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CreateOrderItemDtoIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCreateOrderItemDto(): void
    {
        $product = ProductFactory::createOne();
        $customerOrder = CustomerOrderFactory::createOne();

        $dto = new CreateOrderItemDto(
            $customerOrder->getId(),
            $product->getId(),
            10
        );

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidOrderId(): void
    {
        $product = ProductFactory::createOne();

        $dto = new CreateOrderItemDto(
            -1,
            $product->getId(),
            10
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid orderId', $violations[0]->getMessage());
    }

    public function testProductIdIsRequired(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();

        $dto = new CreateOrderItemDto(
            $customerOrder->getId(),
            null,
            10
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a product Id', $violations[0]->getMessage());
    }

    public function testInvalidProductId(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();

        $dto = new CreateOrderItemDto(
            $customerOrder->getId(),
            100,
            10
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Product with Id "100" not found.', $violations[0]->getMessage());
    }

    public function testQuantityIsRequired(): void
    {
        $product = ProductFactory::createOne();
        $customerOrder = CustomerOrderFactory::createOne();

        $dto = new CreateOrderItemDto(
            $customerOrder->getId(),
            $product->getId(),
            null
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a product quantity', $violations[0]->getMessage());
    }

    public function testInvalidQuantity(): void
    {
        $product = ProductFactory::createOne();
        $customerOrder = CustomerOrderFactory::createOne();

        $dto = new CreateOrderItemDto(
            $customerOrder->getId(),
            $product->getId(),
            0
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a product quantity (1 to 100000)', $violations[0]->getMessage());
    }
}
