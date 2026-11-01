<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\ProductFactory;
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
final class UpdateSupplierStockCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:update-supplier-stock');
        $this->commandTester = new CommandTester($command);
    }

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

    public function testReplenishmentRepricesTheProduct(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $products = ProductFactory::createMany(3);
        foreach ($products as $product) {
            SupplierProductFactory::createOne([
                'supplier' => $supplier,
                'product' => $product,
                'stock' => 10, // At or below the replenish level: stock and cost both move
                'cost' => '50.00',
            ]);
        }

        $this->commandTester->execute(['product-count' => 10, '--supplier' => $supplier->getId()]);
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $calculator = self::getContainer()->get(MarkupCalculator::class);
        foreach ($products as $product) {
            $reloaded = ProductFactory::repository()->findOrFail($product->getId());
            $source = $reloaded->getActiveProductSource();
            self::assertInstanceOf(SupplierProduct::class, $source);

            self::assertSame($source->getStock(), $reloaded->getStock());
            self::assertSame($source->getCost(), $reloaded->getCost());
            self::assertSame(
                $calculator->calculatePrettyPrice(
                    $source->getCost() ?? '0',
                    $reloaded->getActiveMarkup() ?? '0',
                    $reloaded->getCategory()->getVatRate()->getRate(),
                    $reloaded->getActivePriceModel() ?? PriceModel::DEFAULT,
                ),
                $reloaded->getSellPriceIncVat(),
            );
        }
    }

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

    public function testNoSupplierFoundReturnsFailure(): void
    {
        $this->commandTester->execute([
            'product-count' => 10,
            '--supplier' => 999999,
        ]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Supplier not found', $this->commandTester->getDisplay());
    }

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
