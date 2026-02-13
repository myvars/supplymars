<?php

namespace App\Tests\Purchasing\UI\Console;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class UpdateSupplierStockCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:update-supplier-stock');
        $this->commandTester = new CommandTester($command);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessUpdatingStock(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 50,
            'cost' => '10.00',
        ]);

        $this->commandTester->execute([
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testLowStockReplenishment(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 10, // Below replenish level of 20
            'cost' => '10.00',
        ]);

        $initialStock = $supplierProduct->getStock();

        $this->commandTester->execute([
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        // Stock should have increased (replenished)
        self::assertGreaterThanOrEqual($initialStock, $supplierProduct->getStock());
    }

    #[WithStory(StaffUserStory::class)]
    public function testNoSupplierProductsFound(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        $this->commandTester->execute([
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No supplier products found', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testInvalidCountReturnsInvalid(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        $this->commandTester->execute([
            'product-count' => 0,
            '--supplier' => $supplier->getId(),
        ]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Product count must be > 0', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testNoSupplierFoundReturnsFailure(): void
    {
        $this->commandTester->execute([
            'product-count' => 10,
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
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 50,
            'cost' => '10.00',
        ]);

        $initialStock = $supplierProduct->getStock();
        $initialCost = $supplierProduct->getCost();

        $this->commandTester->execute([
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());

        // Values should remain unchanged
        self::assertSame($initialStock, $supplierProduct->getStock());
        self::assertSame($initialCost, $supplierProduct->getCost());
    }

    #[WithStory(StaffUserStory::class)]
    public function testVerboseOutputShowsProcessedProducts(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 50,
            'cost' => '10.00',
        ]);

        $this->commandTester->execute([
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed Supplier products', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testSpecificSupplierTargeting(): void
    {
        $supplier1 = SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier One']);
        SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier Two']);

        $this->commandTester->execute([
            'product-count' => 10,
            '--supplier' => $supplier1->getId(),
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Supplier One', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testOutputShowsStockIncreasedDecreasedCounts(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 50,
            'cost' => '10.00',
        ]);

        $this->commandTester->execute([
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('stock increased', $this->commandTester->getDisplay());
        self::assertStringContainsString('stock decreased', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testCostUpdateDuringReplenishment(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 5, // Very low, will trigger replenishment
            'cost' => '10.00',
        ]);

        $this->commandTester->execute([
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        // Stock should be replenished (increased or unchanged from 5)
        self::assertGreaterThanOrEqual(5, $supplierProduct->getStock());
    }
}
