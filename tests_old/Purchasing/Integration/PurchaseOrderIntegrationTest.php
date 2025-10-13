<?php

namespace App\Tests\Purchasing\Integration;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\PurchaseOrderFactory;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use tests\Shared\Factory\SupplierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class PurchaseOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidPurchaseOrder(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();
        $supplier = SupplierFactory::createOne();

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $customerOrder,
            'supplier' => $supplier,
        ]);

        $errors = $this->validator->validate($purchaseOrder);
        $this->assertCount(0, $errors);
    }

    public function testPurchaseOrderPersistence(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();
        $supplier = SupplierFactory::createOne();

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $customerOrder,
            'supplier' => $supplier,
        ]);

        $persistedPurchaseOrder = PurchaseOrderFactory::repository()->find($purchaseOrder->getId());
        $this->assertEquals($purchaseOrder->getId(), $persistedPurchaseOrder->getId());
    }

    public function testAddPurchaseOrderItemToPurchaseOrder(): void
    {
        $item = PurchaseOrderItemFactory::createOne();
        $purchaseOrder = $item->getPurchaseOrder();

        $purchaseOrder->addPurchaseOrderItem($item);

        $this->assertTrue($purchaseOrder->getPurchaseOrderItems()->contains($item));
    }

    public function testRemovePurchaseOrderItemFromPurchaseOrder(): void
    {
        $item = PurchaseOrderItemFactory::createOne();
        $purchaseOrder = $item->getPurchaseOrder();

        $purchaseOrder->removePurchaseOrderItem($item);

        $this->assertFalse($purchaseOrder->getPurchaseOrderItems()->contains($item));
    }

    public function testReAddPurchaseOrderItemToPurchaseOrder(): void
    {
        $item = PurchaseOrderItemFactory::createOne();
        $purchaseOrder = $item->getPurchaseOrder();

        $purchaseOrder->addPurchaseOrderItem($item);
        $purchaseOrder->removePurchaseOrderItem($item);
        $purchaseOrder->addPurchaseOrderItem($item);

        $this->assertTrue($purchaseOrder->getPurchaseOrderItems()->contains($item));
    }

    public function testGenerateStatus(): void
    {
        $item = PurchaseOrderItemFactory::createOne();
        $purchaseOrder = $item->getPurchaseOrder();

        $purchaseOrder->addPurchaseOrderItem($item);
        $purchaseOrder->generateStatus();

        $this->assertSame(PurchaseOrderStatus::getDefault(), $purchaseOrder->getStatus());
    }

    public function testGetLineCount(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createMany(2, ['customerOrder' => $order]);

        $this->assertSame(2, $order->getLineCount());
    }

    public function testGetItemCount(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createMany(2, ['customerOrder' => $order, 'quantity' => 2]);

        $this->assertSame(4, $order->getItemCount());
    }
}
