<?php

namespace App\Tests\Purchasing\UI\Console\Utilities;

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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class RewindMixedStatusPurchaseOrdersCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:rewind-mixed-status-purchase-orders');
        $this->commandTester = new CommandTester($command);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $this->em = $em;
    }

    #[WithStory(StaffUserStory::class)]
    public function testNoMatchingPurchaseOrders(): void
    {
        $this->commandTester->execute(['--limit' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No purchase orders found', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testRewindsPurchaseOrderWithMixedStatuses(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct1 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product1,
            'stock' => 100,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product2,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem1 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 5,
        ]);
        $orderItem2 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product2,
            'quantity' => 3,
        ]);

        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $poItem1 = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem1,
            'supplierProduct' => $supplierProduct1,
            'supplier' => $supplier,
            'product' => $product1,
            'quantity' => 5,
        ]);

        $poItem2 = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem2,
            'supplierProduct' => $supplierProduct2,
            'supplier' => $supplier,
            'product' => $product2,
            'quantity' => 3,
        ]);

        // Create mixed statuses: one REJECTED, one ACCEPTED
        $poItem1->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem1->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $poItem2->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem2->updateItemStatus(PurchaseOrderStatus::ACCEPTED);

        $this->em->flush();

        self::assertSame(PurchaseOrderStatus::REJECTED, $poItem1->getStatus());
        self::assertSame(PurchaseOrderStatus::ACCEPTED, $poItem2->getStatus());

        $this->commandTester->execute(['--limit' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed 1 purchase order', $this->commandTester->getDisplay());
        self::assertSame(PurchaseOrderStatus::PENDING, $poItem1->getStatus());
        self::assertSame(PurchaseOrderStatus::PENDING, $poItem2->getStatus());
        self::assertSame(PurchaseOrderStatus::PENDING, $po->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testDryRunDoesNotPersistChanges(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct1 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product1,
            'stock' => 100,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product2,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem1 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 5,
        ]);
        $orderItem2 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product2,
            'quantity' => 3,
        ]);

        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $poItem1 = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem1,
            'supplierProduct' => $supplierProduct1,
            'supplier' => $supplier,
            'product' => $product1,
            'quantity' => 5,
        ]);

        $poItem2 = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem2,
            'supplierProduct' => $supplierProduct2,
            'supplier' => $supplier,
            'product' => $product2,
            'quantity' => 3,
        ]);

        $poItem1->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem1->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $poItem2->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem2->updateItemStatus(PurchaseOrderStatus::ACCEPTED);

        $this->em->flush();

        $this->commandTester->execute([
            '--limit' => 10,
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        self::assertSame(PurchaseOrderStatus::REJECTED, $poItem1->getStatus());
        self::assertSame(PurchaseOrderStatus::ACCEPTED, $poItem2->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testVerboseOutputShowsDetails(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct1 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product1,
            'stock' => 100,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product2,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem1 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 5,
        ]);
        $orderItem2 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product2,
            'quantity' => 3,
        ]);

        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $poItem1 = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem1,
            'supplierProduct' => $supplierProduct1,
            'supplier' => $supplier,
            'product' => $product1,
            'quantity' => 5,
        ]);

        $poItem2 = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem2,
            'supplierProduct' => $supplierProduct2,
            'supplier' => $supplier,
            'product' => $product2,
            'quantity' => 3,
        ]);

        $poItem1->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem1->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $poItem2->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem2->updateItemStatus(PurchaseOrderStatus::ACCEPTED);

        $this->em->flush();

        $this->commandTester->execute(
            ['--limit' => 10, '--dry-run' => true],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('REJECTED', $this->commandTester->getDisplay());
        self::assertStringContainsString('ACCEPTED', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testDoesNotMatchPOWithoutRejectedItems(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct1 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product1,
            'stock' => 100,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product2,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem1 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 5,
        ]);
        $orderItem2 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product2,
            'quantity' => 3,
        ]);

        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $poItem1 = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem1,
            'supplierProduct' => $supplierProduct1,
            'supplier' => $supplier,
            'product' => $product1,
            'quantity' => 5,
        ]);

        $poItem2 = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem2,
            'supplierProduct' => $supplierProduct2,
            'supplier' => $supplier,
            'product' => $product2,
            'quantity' => 3,
        ]);

        // Mixed statuses but NO REJECTED
        $poItem1->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        $poItem2->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem2->updateItemStatus(PurchaseOrderStatus::ACCEPTED);

        $this->em->flush();

        $this->commandTester->execute(['--limit' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No purchase orders found', $this->commandTester->getDisplay());
    }
}
