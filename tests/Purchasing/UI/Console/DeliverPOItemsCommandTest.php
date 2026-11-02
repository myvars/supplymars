<?php

declare(strict_types=1);

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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class DeliverPOItemsCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

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

        $result = self::runCommand('app:deliver-purchase-order-items', [
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
            '--skip-timing' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('delivered', $result->getDisplay());
        self::assertSame(PurchaseOrderStatus::DELIVERED, $poItem->getStatus());
    }

    public function testNoShippedItemsToDeliver(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        $result = self::runCommand('app:deliver-purchase-order-items', [
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('No shipped PO items to deliver', $result->getDisplay());
    }

    public function testInvalidCountReturnsInvalid(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        $result = self::runCommand('app:deliver-purchase-order-items', [
            'po-item-count' => 0,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsInvalid($result);
        self::assertStringContainsString('poItemCount must be > 0', $result->getDisplay());
    }

    public function testNoSupplierFoundReturnsFailure(): void
    {
        $result = self::runCommand('app:deliver-purchase-order-items', [
            'po-item-count' => 10,
            '--supplier' => 999999,
        ]);

        $this->assertCommandFailed($result);
        self::assertStringContainsString('Supplier not found', $result->getDisplay());
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
        $poItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $poItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);

        $this->em->flush();

        $result = self::runCommand('app:deliver-purchase-order-items', [
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
            '--skip-timing' => true,
            '--dry-run' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('[DRY RUN]', $result->getDisplay());
        self::assertSame(PurchaseOrderStatus::SHIPPED, $poItem->getStatus());
    }

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

        $result = self::runCommand('app:deliver-purchase-order-items', [
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed', $result->getDisplay());
    }

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

        $result = self::runCommand('app:deliver-purchase-order-items', [
            'po-item-count' => 10,
            '--supplier' => $supplier->getId(),
            '--skip-timing' => true,
        ], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Delivered PO Item IDs', $result->getDisplay());
    }

    public function testSpecificSupplierTargeting(): void
    {
        $supplier1 = SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier One']);
        SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier Two']);

        $result = self::runCommand('app:deliver-purchase-order-items', [
            'po-item-count' => 10,
            '--supplier' => $supplier1->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Supplier One', $result->getDisplay());
    }
}
