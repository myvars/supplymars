<?php

namespace App\Tests\Order\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\PurchaseOrderItemFactory;
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

    public function testCreateWithInvalidMaxQty(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne(['quantity' => 10001]);

        $violations = $this->validator->validate($customerOrderItem);
        $this->assertSame('Quantity must be between 1 and 10000', $violations[0]->getMessage());
    }

    public function testUpdateWithInvalidMaxQty(): void
    {
        $quantity = 10001;

        $customerOrderItem = CustomerOrderItemFactory::createOne();
        $customerOrderItem->updateItem(
            $quantity,
            $customerOrderItem->getPrice(),
            $customerOrderItem->getPriceIncVat(),
            $customerOrderItem->getWeight(),
        );

        $violations = $this->validator->validate($customerOrderItem);
        $this->assertSame('Quantity must be between 1 and 10000', $violations[0]->getMessage());
    }

    public function testUpdateWithInvalidMaxPrice(): void
    {
        $price = '10000001.00';

        $customerOrderItem = CustomerOrderItemFactory::createOne();
        $customerOrderItem->updateItem(
            $customerOrderItem->getQuantity(),
            $price,
            $customerOrderItem->getPriceIncVat(),
            $customerOrderItem->getWeight(),
        );

        $violations = $this->validator->validate($customerOrderItem);
        $this->assertSame('Price must be between 0 and 10000000', $violations[0]->getMessage());
    }

    public function testUpdateWithInvalidMaxPriceIncVat(): void
    {
        $priceIncVat = '10000001.00';

        $customerOrderItem = CustomerOrderItemFactory::createOne();
        $customerOrderItem->updateItem(
            $customerOrderItem->getQuantity(),
            $customerOrderItem->getPrice(),
            $priceIncVat,
            $customerOrderItem->getWeight(),
        );

        $violations = $this->validator->validate($customerOrderItem);
        $this->assertSame('Price inc VAT must be between 0 and 10000000', $violations[0]->getMessage());
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

    public function testUpdateCustomerOrderItem(): void
    {
        $quantity = 10;

        $customerOrderItem = CustomerOrderItemFactory::createOne();
        $customerOrderItem->updateItem(
            $quantity,
            $customerOrderItem->getPrice(),
            $customerOrderItem->getPriceIncVat(),
            $customerOrderItem->getWeight(),
        );

        $this->assertEquals($quantity, $customerOrderItem->getQuantity());
    }

    public function testUpdateQtyGreaterThanQtyAllocatedToPo(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $customerOrderItem = $purchaseOrderItem->getCustomerOrderItem();
        $customerOrderItem->addPurchaseOrderItem($purchaseOrderItem);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Cannot edit this allocated qty below ' . $customerOrderItem->getQtyAddedToPurchaseOrders()
        );

        $quantity = 0;

        $customerOrderItem->updateItem(
            $quantity,
            $customerOrderItem->getPrice(),
            $customerOrderItem->getPriceIncVat(),
            $customerOrderItem->getWeight(),
        );
    }
}
