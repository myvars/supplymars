<?php

namespace App\Tests\Purchasing\UI\Console;

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

class DeliverPOItemsCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:deliver-purchase-order-items');
        $this->commandTester = new CommandTester($command);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessDeliveringWithSkipTiming(): void
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

        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);
        $poItem = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        // Transition to SHIPPED status
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $poItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);

        $this->em->flush();

        $this->commandTester->execute([
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
            '--skip-timing' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('delivered', $this->commandTester->getDisplay());
        self::assertSame(PurchaseOrderStatus::DELIVERED, $poItem->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testNoShippedItemsToDeliver(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        $this->commandTester->execute([
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No shipped PO items to deliver', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testInvalidCountReturnsInvalid(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        $this->commandTester->execute([
            'po-item-count' => 0,
            '--supplier' => $supplier->getId(),
        ]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('poItemCount must be > 0', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testNoSupplierFoundReturnsFailure(): void
    {
        $this->commandTester->execute([
            'po-item-count' => 10,
            '--supplier' => 999999,
        ]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Supplier not found', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
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
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);
        $poItem = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $poItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);

        $this->em->flush();

        $this->commandTester->execute([
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
            '--skip-timing' => true,
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        self::assertSame(PurchaseOrderStatus::SHIPPED, $poItem->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testTimingChecksAppliedWithoutSkipTiming(): void
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

        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);
        $poItem = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $poItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);

        $this->em->flush();

        $this->commandTester->execute([
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testVerboseOutputShowsDeliveredIds(): void
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

        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);
        $poItem = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $poItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);

        $this->em->flush();

        $this->commandTester->execute([
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
            '--skip-timing' => true,
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Delivered PO Item IDs', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testSpecificSupplierTargeting(): void
    {
        $supplier1 = SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier One']);
        SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier Two']);

        $this->commandTester->execute([
            'po-item-count' => 10,
            '--supplier' => $supplier1->getId(),
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Supplier One', $this->commandTester->getDisplay());
    }
}
