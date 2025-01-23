<?php

namespace App\Tests\New\Integration\Entity;

use App\Factory\CustomerOrderFactory;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\ProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CustomerOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCustomerOrderItem(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();
        $product = ProductFactory::createOne();

        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 5,
        ]);

        $errors = $this->validator->validate($customerOrderItem);
        $this->assertCount(0, $errors);
    }

    public function testQuantityMustBeInRange(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne(['quantity' => 100001]);

        $violations = $this->validator->validate($customerOrderItem);
        $this->assertSame('Please enter a product quantity (0 to 100000)', $violations[0]->getMessage());
    }

    public function testCustomerOrderItemPersistence(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();
        $product = ProductFactory::createOne();

        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 5,
        ]);

        $persistedCustomerOrderItem = CustomerOrderItemFactory::repository()->find($customerOrderItem->getId());
        $this->assertEquals($customerOrderItem->getId(), $persistedCustomerOrderItem->getId());
    }
}