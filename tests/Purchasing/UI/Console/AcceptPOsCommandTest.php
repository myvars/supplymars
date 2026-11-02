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
final class AcceptPOsCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSuccessWithWaitingPurchaseOrders(): void
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

        // Create a PO with PROCESSING status (waiting for acceptance)
        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);
        PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        // Transition to PROCESSING status and flush to database
        foreach ($po->getPurchaseOrderItems() as $item) {
            $item->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        }

        $this->em->flush();

        $result = self::runCommand('app:accept-purchase-orders', [
            'po-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed', $result->getDisplay());
    }

    public function testNoWaitingPurchaseOrders(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        $result = self::runCommand('app:accept-purchase-orders', [
            'po-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('No waiting purchase orders', $result->getDisplay());
    }

    public function testInvalidCountReturnsInvalid(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        $result = self::runCommand('app:accept-purchase-orders', [
            'po-count' => 0,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsInvalid($result);
        self::assertStringContainsString('poCount must be > 0', $result->getDisplay());
    }

    public function testNoSupplierFoundReturnsFailure(): void
    {
        $result = self::runCommand('app:accept-purchase-orders', [
            'po-count' => 10,
            '--supplier' => 999999, // Non-existent supplier
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

        foreach ($po->getPurchaseOrderItems() as $item) {
            $item->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        }

        $result = self::runCommand('app:accept-purchase-orders', [
            'po-count' => 10,
            '--supplier' => $supplier->getId(),
            '--dry-run' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('[DRY RUN]', $result->getDisplay());

        // Status should remain PROCESSING
        self::assertSame(PurchaseOrderStatus::PROCESSING, $poItem->getStatus());
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
        PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        foreach ($po->getPurchaseOrderItems() as $item) {
            $item->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        }

        $this->em->flush();

        $result = self::runCommand('app:accept-purchase-orders', [
            'po-count' => 10,
            '--supplier' => $supplier->getId(),
        ], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed PO IDs', $result->getDisplay());
    }

    public function testSpecificSupplierTargeting(): void
    {
        $supplier1 = SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier One']);
        SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier Two']);

        $result = self::runCommand('app:accept-purchase-orders', [
            'po-count' => 10,
            '--supplier' => $supplier1->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Supplier One', $result->getDisplay());
    }
}
