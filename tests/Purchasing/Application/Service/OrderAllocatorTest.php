<?php

namespace App\Tests\Purchasing\Application\Service;

use App\Purchasing\Application\Service\OrderAllocator;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
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

class OrderAllocatorTest extends KernelTestCase
{
    use Factories;

    private OrderAllocator $allocator;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->allocator = self::getContainer()->get(OrderAllocator::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testProcessAllocatesAllItemsWithSufficientStock(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->allocator->process($order);
        $this->em->flush();

        self::assertTrue($this->allocator->allItemsAllocated($order));
        self::assertCount(1, $order->getPurchaseOrders());
    }

    #[WithStory(StaffUserStory::class)]
    public function testProcessSkipsItemsWithZeroOutstandingQty(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
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

        // Pre-allocate the order item
        PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        $initialPurchaseOrderCount = $order->getPurchaseOrders()->count();

        $this->allocator->process($order);
        $this->em->flush();

        // No new PO items should be created
        self::assertSame($initialPurchaseOrderCount, $order->getPurchaseOrders()->count());
    }

    #[WithStory(StaffUserStory::class)]
    public function testProcessSkipsItemsWithNoSupplierProduct(): void
    {
        // Create a product without any supplier product source
        $product = ProductFactory::createOne(['isActive' => true]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->allocator->process($order);
        $this->em->flush();

        // Item should not be allocated (no POs created)
        self::assertFalse($this->allocator->allItemsAllocated($order));
        self::assertCount(0, $order->getPurchaseOrders());
    }

    #[WithStory(StaffUserStory::class)]
    public function testProcessContinuesOnPerItemFailures(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        // Product with supplier source
        $productWithSource = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $productWithSource,
            'stock' => 100,
        ]);

        // Product without supplier source
        $productWithoutSource = ProductFactory::createOne(['isActive' => true]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $productWithoutSource,
            'quantity' => 5,
        ]);
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $productWithSource,
            'quantity' => 3,
        ]);

        $this->allocator->process($order);
        $this->em->flush();

        // Only the item with a supplier source should be allocated
        self::assertFalse($this->allocator->allItemsAllocated($order));
        self::assertCount(1, $order->getPurchaseOrders());
    }

    #[WithStory(StaffUserStory::class)]
    public function testProcessUpdatesPurchaseOrderItemStatusWhenAllAllocated(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->allocator->process($order);
        $this->em->flush();

        self::assertTrue($this->allocator->allItemsAllocated($order));

        foreach ($order->getPurchaseOrders() as $purchaseOrder) {
            foreach ($purchaseOrder->getPurchaseOrderItems() as $poItem) {
                self::assertSame(PurchaseOrderStatus::PROCESSING, $poItem->getStatus());
            }
        }
    }

    #[WithStory(StaffUserStory::class)]
    public function testProcessDoesNotUpdateStatusWhenPartiallyAllocated(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        // Product with supplier source
        $productWithSource = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $productWithSource,
            'stock' => 100,
        ]);

        // Product without supplier source
        $productWithoutSource = ProductFactory::createOne(['isActive' => true]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $productWithoutSource,
            'quantity' => 5,
        ]);
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $productWithSource,
            'quantity' => 3,
        ]);

        $this->allocator->process($order);
        $this->em->flush();

        self::assertFalse($this->allocator->allItemsAllocated($order));

        // Status should remain PENDING for partially allocated orders
        foreach ($order->getPurchaseOrders() as $purchaseOrder) {
            foreach ($purchaseOrder->getPurchaseOrderItems() as $poItem) {
                self::assertSame(PurchaseOrderStatus::PENDING, $poItem->getStatus());
            }
        }
    }

    #[WithStory(StaffUserStory::class)]
    public function testAllItemsAllocatedReturnsTrueWhenFullyAllocated(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
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

        // Fully allocate by creating a purchase order item linked to this customer order item
        PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        self::assertTrue($this->allocator->allItemsAllocated($order));
    }

    #[WithStory(StaffUserStory::class)]
    public function testAllItemsAllocatedReturnsFalseWhenOutstandingExists(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
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

        // Partially allocate (only 3 of 5)
        PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 3,
        ]);

        self::assertFalse($this->allocator->allItemsAllocated($order));
    }

    #[WithStory(StaffUserStory::class)]
    public function testProcessCreatesNewPurchaseOrderForSupplier(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        self::assertCount(0, $order->getPurchaseOrders());

        $this->allocator->process($order);
        $this->em->flush();

        self::assertCount(1, $order->getPurchaseOrders());
        $purchaseOrder = $order->getPurchaseOrders()->first();
        self::assertSame($supplier->getId(), $purchaseOrder->getSupplier()->getId());
    }

    #[WithStory(StaffUserStory::class)]
    public function testProcessUsesExistingEditablePurchaseOrder(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);

        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product1,
            'stock' => 100,
        ]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product2,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();

        // Create an existing editable PO for the supplier
        $existingPurchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 5,
        ]);
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product2,
            'quantity' => 3,
        ]);

        $this->allocator->process($order);
        $this->em->flush();

        // Should reuse existing PO, not create a new one
        self::assertCount(1, $order->getPurchaseOrders());
        self::assertSame($existingPurchaseOrder->getId(), $order->getPurchaseOrders()->first()->getId());
    }
}
