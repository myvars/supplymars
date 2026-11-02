<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
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

#[WithStory(StaffUserStory::class)]
final class RefundPOsCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:refund-purchase-orders');
        $this->commandTester = new CommandTester($command);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSuccessRefundingRejectedPOs(): void
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

        // Transition to REJECTED status
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $this->em->flush();

        $this->commandTester->execute(['po-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Refunded', $this->commandTester->getDisplay());
        self::assertSame(PurchaseOrderStatus::REFUNDED, $poItem->getStatus());
    }

    public function testRefundsOnlyTheRejectedItemsOfAPurchaseOrder(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();
        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $addItem = static function () use ($supplier, $order, $po): PurchaseOrderItem {
            $product = ProductFactory::createOne(['isActive' => true]);
            SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => $product, 'stock' => 100]);

            return PurchaseOrderItemFactory::createOne([
                'purchaseOrder' => $po,
                'customerOrder' => $order,
                'customerOrderItem' => CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product]),
                'supplier' => $supplier,
                'product' => $product,
            ]);
        };
        $rejected = $addItem();
        $shipped = $addItem();

        // The PO takes its lowest item status, so ship one item before rejecting the other
        foreach ([PurchaseOrderStatus::PROCESSING, PurchaseOrderStatus::ACCEPTED, PurchaseOrderStatus::SHIPPED] as $status) {
            $shipped->updateItemStatus($status);
        }

        $rejected->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $rejected->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $this->em->flush();
        self::assertSame(PurchaseOrderStatus::REJECTED, $po->getStatus());

        $this->commandTester->execute(['po-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertSame(PurchaseOrderStatus::REFUNDED, $rejected->getStatus());
        self::assertSame(PurchaseOrderStatus::SHIPPED, $shipped->getStatus());
    }

    public function testNoRejectedPurchaseOrders(): void
    {
        $this->commandTester->execute(['po-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No rejected purchase orders', $this->commandTester->getDisplay());
    }

    public function testInvalidCountReturnsInvalid(): void
    {
        $this->commandTester->execute(['po-count' => 0]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('poCount must be > 0', $this->commandTester->getDisplay());
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
        $poItem->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $this->em->flush();

        $this->commandTester->execute([
            'po-count' => 10,
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        self::assertSame(PurchaseOrderStatus::REJECTED, $poItem->getStatus());
    }

    public function testReallocationTriggeredAfterRefund(): void
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
        $poItem->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $this->em->flush();

        $this->commandTester->execute(['po-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('reallocation', $this->commandTester->getDisplay());
    }

    public function testVerboseOutputShowsProcessedIds(): void
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
        $poItem->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $this->em->flush();

        $this->commandTester->execute(
            ['po-count' => 10],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed PO IDs', $this->commandTester->getDisplay());
    }

    public function testOutputShowsRefundedAndReallocatedCounts(): void
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
        $poItem->updateItemStatus(PurchaseOrderStatus::REJECTED);

        $this->em->flush();

        $this->commandTester->execute(['po-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('item(s)', $this->commandTester->getDisplay());
        self::assertStringContainsString('reallocation', $this->commandTester->getDisplay());
    }
}
