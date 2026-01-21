<?php

namespace App\Tests\Purchasing\Application\Service;

use App\Purchasing\Application\Service\OrderItemAllocator;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class OrderItemAllocatorTest extends KernelTestCase
{
    use Factories;

    private OrderItemAllocator $allocator;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->allocator = self::getContainer()->get(OrderItemAllocator::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testForOrderItemCreatesNewPurchaseOrderItem(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $poItem = $this->allocator->forOrderItem($purchaseOrder, $orderItem, $supplierProduct);
        $this->em->flush();

        self::assertNotNull($poItem->getId());
        self::assertSame(5, $poItem->getQuantity());
        self::assertSame($orderItem->getId(), $poItem->getCustomerOrderItem()->getId());
    }

    #[WithStory(StaffUserStory::class)]
    public function testForOrderItemUpdatesExistingItemQuantity(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 10,
        ]);

        // Create existing PO item with partial allocation
        $existingPoItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        $purchaseOrder = $existingPoItem->getPurchaseOrder();

        $poItem = $this->allocator->forOrderItem($purchaseOrder, $orderItem, $supplierProduct);
        $this->em->flush();

        // Should update to max quantity (outstanding + existing = 5 + 5 = 10)
        self::assertSame($existingPoItem->getId(), $poItem->getId());
        self::assertSame(10, $poItem->getQuantity());
    }

    #[WithStory(StaffUserStory::class)]
    public function testForOrderItemThrowsWhenNoQuantityToAllocate(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        // Fully allocate the order item in a different PO
        PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        // Create a new PO (so no existing item in this PO)
        $newPurchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No quantity to allocate');

        $this->allocator->forOrderItem($newPurchaseOrder, $orderItem, $supplierProduct);
    }

    #[WithStory(StaffUserStory::class)]
    public function testForOrderItemRecalculatesPurchaseOrderTotal(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
            'cost' => '10.00',
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $initialTotal = (float) $purchaseOrder->getTotalPrice();

        $this->allocator->forOrderItem($purchaseOrder, $orderItem, $supplierProduct);
        $this->em->flush();

        // New item adds 5 * 10.00 = 50.00 to total
        $expectedTotal = $initialTotal + 50.0;
        self::assertEquals($expectedTotal, (float) $purchaseOrder->getTotalPrice());
    }

    #[WithStory(StaffUserStory::class)]
    public function testForOrderItemLinksCustomerOrderItemCorrectly(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $poItem = $this->allocator->forOrderItem($purchaseOrder, $orderItem, $supplierProduct);
        $this->em->flush();

        // Check bidirectional link
        self::assertSame($orderItem->getId(), $poItem->getCustomerOrderItem()->getId());
        self::assertTrue($orderItem->getPurchaseOrderItems()->contains($poItem));
    }
}
