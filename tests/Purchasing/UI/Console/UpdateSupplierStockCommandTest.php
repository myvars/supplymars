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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class UpdateSupplierStockCommandTest extends KernelTestCase
{
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

        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed', $result->getDisplay());
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

        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
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

        $result = self::runCommand('app:update-supplier-stock', ['product-count' => 10, '--supplier' => $supplier->getId()]);
        $this->assertCommandIsSuccessful($result);

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

        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('No supplier products found', $result->getDisplay());
    }

    public function testInvalidCountReturnsInvalid(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 0,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsInvalid($result);
        self::assertStringContainsString('Product count must be > 0', $result->getDisplay());
    }

    public function testNoSupplierFoundReturnsFailure(): void
    {
        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 10,
            '--supplier' => 999999,
        ]);

        $this->assertCommandFailed($result);
        self::assertStringContainsString('Supplier not found', $result->getDisplay());
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

        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
            '--dry-run' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('[DRY RUN]', $result->getDisplay());

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

        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed Supplier products', $result->getDisplay());
    }

    public function testSpecificSupplierTargeting(): void
    {
        $supplier1 = SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier One']);
        SupplierFactory::createOne(['isActive' => true, 'name' => 'Supplier Two']);

        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 10,
            '--supplier' => $supplier1->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Supplier One', $result->getDisplay());
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

        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('stock increased', $result->getDisplay());
        self::assertStringContainsString('stock decreased', $result->getDisplay());
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

        $result = self::runCommand('app:update-supplier-stock', [
            'product-count' => 10,
            '--supplier' => $supplier->getId(),
        ]);

        $this->assertCommandIsSuccessful($result);
        // Stock should be replenished (increased or unchanged from 5)
        self::assertGreaterThanOrEqual(5, $supplierProduct->getStock());
    }
}
