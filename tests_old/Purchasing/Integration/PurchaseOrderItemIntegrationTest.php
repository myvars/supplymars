<?php

namespace App\Tests\Purchasing\Integration;

use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\PurchaseOrderFactory;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use tests\Shared\Factory\SupplierFactory;
use tests\Shared\Factory\SupplierProductFactory;
use Zenstruck\Foundry\Test\Factories;

class PurchaseOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
        StaffUserStory::load();
    }

    public function testValidPurchaseOrderItem(): void
    {
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
        ]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 5,
        ]);
        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $customerOrder,
            'supplier' => $supplier,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct,
            'quantity' => 5,
        ]);

        $errors = $this->validator->validate($purchaseOrderItem);
        $this->assertCount(0, $errors);
    }

    public function testPurchaseOrderItemPersistence(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $persistedPurchaseOrderItem = PurchaseOrderItemFactory::repository()->find($purchaseOrderItem->getId());
        $this->assertEquals($purchaseOrderItem->getId(), $persistedPurchaseOrderItem->getId());
    }

    public function testUpdatePurchaseOrderItem(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne(['quantity' => 10]);
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
        ]);
        $lineTotal = $purchaseOrderItem->getTotalPrice();

        $purchaseOrderItem->updateItemQuantity(10);

        $this->assertEquals(10, $purchaseOrderItem->getQuantity());
        $this->assertEquals(bcmul($lineTotal, 10, 2), $purchaseOrderItem->getTotalPrice());
    }

    public function testInvalidUpdatePurchaseOrderItemWithHighQty(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $maxQuantity = $purchaseOrderItem->getMaxQuantity();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Quantity cannot be greater than ' . $maxQuantity);
        $purchaseOrderItem->updateItemQuantity($maxQuantity + 1);
    }
}
