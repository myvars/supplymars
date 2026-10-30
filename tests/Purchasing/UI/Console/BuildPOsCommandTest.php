<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

#[WithStory(StaffUserStory::class)]
final class BuildPOsCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:build-purchase-orders');
        $this->commandTester = new CommandTester($command);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSuccessWithPendingOrders(): void
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

        $this->commandTester->execute(['order-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed', $this->commandTester->getDisplay());
    }

    public function testNoCustomerOrdersToProcess(): void
    {
        $this->commandTester->execute(['order-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No customer orders to process', $this->commandTester->getDisplay());
    }

    public function testInvalidCountReturnsInvalid(): void
    {
        $this->commandTester->execute(['order-count' => 0]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('orderCount must be > 0', $this->commandTester->getDisplay());
    }

    public function testDryRunDoesNotPersistChanges(): void
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

        $this->commandTester->execute([
            'order-count' => 10,
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());

        // Order should have no POs (dry run)
        self::assertCount(0, $order->getPurchaseOrders());
    }

    public function testMultiSupplierAllocation(): void
    {
        $supplier1 = SupplierFactory::createOne(['isActive' => true]);
        $supplier2 = SupplierFactory::createOne(['isActive' => true]);

        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);

        SupplierProductFactory::createOne([
            'supplier' => $supplier1,
            'product' => $product1,
            'stock' => 100,
        ]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier2,
            'product' => $product2,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
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

        $this->commandTester->execute(['order-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        // Both products should be allocated (possibly to different suppliers)
        self::assertGreaterThanOrEqual(1, $order->getPurchaseOrders()->count());
    }

    public function testVerboseOutputShowsProcessedOrderIds(): void
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

        $this->commandTester->execute(
            ['order-count' => 10],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed Order IDs', $this->commandTester->getDisplay());
    }

    public function testOutputShowsPOsCreatedAndItemsAllocated(): void
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

        $this->commandTester->execute(['order-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('PO(s)', $this->commandTester->getDisplay());
        self::assertStringContainsString('item(s)', $this->commandTester->getDisplay());
    }

    public function testRetriesLineThatHadNoStock(): void
    {
        $stockedSource = $this->sourceWithStock(100);
        $waitingSource = $this->sourceWithStock(0);

        $order = CustomerOrderFactory::createOne();
        $stockedItem = $this->orderItem($order, $stockedSource->getProduct(), 5);
        $waitingItem = $this->orderItem($order, $waitingSource->getProduct(), 3);

        $this->commandTester->execute(['order-count' => 10]);

        self::assertCount(1, $stockedItem->getPurchaseOrderItems());
        self::assertSame(3, $waitingItem->getOutstandingQty());

        $waitingSource->updateStock(10);
        $this->em->flush();

        $this->commandTester->execute(['order-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertSame(0, $waitingItem->getOutstandingQty());
        foreach ($order->getPurchaseOrders() as $purchaseOrder) {
            self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrder->getStatus());
        }
    }

    public function testRetriesLineWhosePurchaseOrderItemWasRefunded(): void
    {
        $source = $this->sourceWithStock(100);
        $order = CustomerOrderFactory::createOne();
        $orderItem = $this->orderItem($order, $source->getProduct(), 5);
        $poItem = $this->purchaseOrderItem($order, $orderItem, $source, 5);

        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem->updateItemStatus(PurchaseOrderStatus::REJECTED);
        $poItem->updateItemStatus(PurchaseOrderStatus::REFUNDED);

        $this->em->flush();

        $this->commandTester->execute(['order-count' => 10]);

        self::assertCount(2, $orderItem->getPurchaseOrderItems());
        self::assertSame(0, $orderItem->getOutstandingQty());
    }

    public function testRetriesLineWhosePurchaseOrderItemWasCancelled(): void
    {
        $source = $this->sourceWithStock(100);
        $order = CustomerOrderFactory::createOne();
        $orderItem = $this->orderItem($order, $source->getProduct(), 5);
        $this->purchaseOrderItem($order, $orderItem, $source, 3)->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $this->purchaseOrderItem($order, $orderItem, $source, 2)->updateItemStatus(PurchaseOrderStatus::CANCELLED);
        $this->em->flush();

        $this->commandTester->execute(['order-count' => 10]);

        self::assertCount(3, $orderItem->getPurchaseOrderItems());
        self::assertSame(0, $orderItem->getOutstandingQty());
        self::assertStringContainsString('allocated 1 item(s)', $this->commandTester->getDisplay());
    }

    public function testSkipsOrderWithNothingOutstanding(): void
    {
        $source = $this->sourceWithStock(100);
        $order = CustomerOrderFactory::createOne();
        $orderItem = $this->orderItem($order, $source->getProduct(), 5);
        $this->purchaseOrderItem($order, $orderItem, $source, 5);
        $this->orderItem($order, $source->getProduct(), 2)->cancelItem();
        $this->em->flush();

        $this->commandTester->execute(['order-count' => 10]);

        self::assertStringContainsString('No customer orders to process', $this->commandTester->getDisplay());
    }

    public function testSkipsLockedOrderWithOutstandingQuantity(): void
    {
        $order = $this->orderItemWithOutstandingQuantity()->getCustomerOrder();
        $staff = UserFactory::new()->asStaff()->create();
        $order->lockOrder($staff, $staff);
        $this->em->flush();

        $this->commandTester->execute(['order-count' => 10]);

        self::assertStringContainsString('No customer orders to process', $this->commandTester->getDisplay());
    }

    public function testTakesNewOrdersBeforeRetries(): void
    {
        $retryItem = $this->orderItemWithOutstandingQuantity();
        $this->em->getConnection()->executeStatement(
            'UPDATE customer_order SET created_at = :createdAt WHERE id = :id',
            ['createdAt' => '2020-01-01 00:00:00', 'id' => $retryItem->getCustomerOrder()->getId()],
        );

        $source = $this->sourceWithStock(100);
        $newOrder = CustomerOrderFactory::createOne();
        $this->orderItem($newOrder, $source->getProduct(), 1);

        $this->commandTester->execute(['order-count' => 1]);

        self::assertCount(1, $newOrder->getPurchaseOrders());
        self::assertSame(3, $retryItem->getOutstandingQty());
    }

    /** A line with 2 of its 5 on a PO, and stock for the other 3. */
    private function orderItemWithOutstandingQuantity(): CustomerOrderItem
    {
        $source = $this->sourceWithStock(100);
        $order = CustomerOrderFactory::createOne();
        $orderItem = $this->orderItem($order, $source->getProduct(), 5);
        $this->purchaseOrderItem($order, $orderItem, $source, 2);

        return $orderItem;
    }

    private function sourceWithStock(int $stock): SupplierProduct
    {
        return SupplierProductFactory::createOne([
            'supplier' => SupplierFactory::createOne(['isActive' => true]),
            'product' => ProductFactory::createOne(['isActive' => true]),
            'stock' => $stock,
        ]);
    }

    private function orderItem(CustomerOrder $order, Product $product, int $quantity): CustomerOrderItem
    {
        return CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => $quantity,
        ]);
    }

    private function purchaseOrderItem(
        CustomerOrder $order,
        CustomerOrderItem $orderItem,
        SupplierProduct $source,
        int $quantity,
    ): PurchaseOrderItem {
        return PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => PurchaseOrderFactory::createOne([
                'customerOrder' => $order,
                'supplier' => $source->getSupplier(),
            ]),
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $source->getSupplier(),
            'supplierProduct' => $source,
            'product' => $orderItem->getProduct(),
            'quantity' => $quantity,
        ]);
    }
}
