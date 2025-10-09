<?php

namespace App\Tests\Integration\Entity;

use App\Factory\CustomerOrderFactory;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\ProductFactory;
use App\Factory\PurchaseOrderItemFactory;
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

    public function testCreateWithInvalidMaxQty(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne(['quantity' => 10001]);

        $violations = $this->validator->validate($customerOrderItem);
        $this->assertSame('Quantity must be between 1 and 10000', $violations[0]->getMessage());
    }

    public function testCreateWithInvalidMaxWeight(): void
    {
        $product = ProductFactory::createOne(['weight' => 10000001]);
        $customerOrderItem = CustomerOrderItemFactory::createOne(['product' => $product]);

        $violations = $this->validator->validate($customerOrderItem);
        $this->assertSame('Weight must be between 0 and 10000000', $violations[0]->getMessage());
    }

    public function testUpdateWithInvalidMaxQty(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne();

        $customerOrderItem->updateItem(10001, '100', '120');

        $violations = $this->validator->validate($customerOrderItem);
        $this->assertSame('Quantity must be between 1 and 10000', $violations[0]->getMessage());
    }

    public function testUpdateWithInvalidMaxPrice(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne();

        $customerOrderItem->updateItem(10, '10000001', '120');

        $violations = $this->validator->validate($customerOrderItem);
        $this->assertSame('Price must be between 0 and 10000000', $violations[0]->getMessage());
    }

    public function testUpdateWithInvalidMaxPriceIncVat(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne();

        $customerOrderItem->updateItem(10, '100', '10000001');

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
        $customerOrderItem = CustomerOrderItemFactory::createOne();
        $customerOrderItem->updateItem(10, '10.00', '12.00');

        $this->assertEquals(10, $customerOrderItem->getQuantity());
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
        $customerOrderItem->updateItem(0, '10.00', '12.00');
    }
}
