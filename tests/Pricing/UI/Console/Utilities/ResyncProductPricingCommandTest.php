<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI\Console\Utilities;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;

final class ResyncProductPricingCommandTest extends KernelTestCase
{
    public function testBringsStaleProductBackInStepWithItsSources(): void
    {
        $product = $this->staleProduct();

        $result = self::runCommand('app:resync-product-pricing');

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('1 changed', $result->getDisplay());

        // Markup 25%, VAT 20%, no rounding: 20.00 × 1.25 × 1.20 = 30.00
        $reloaded = $this->reload($product);
        self::assertSame('20.00', $reloaded->getCost());
        self::assertSame(40, $reloaded->getStock());
        self::assertSame('30.00', $reloaded->getSellPriceIncVat());
    }

    public function testSecondRunChangesNothing(): void
    {
        $this->staleProduct();

        self::runCommand('app:resync-product-pricing');
        $result = self::runCommand('app:resync-product-pricing');

        self::assertStringContainsString('0 changed', $result->getDisplay());
    }

    public function testDryRunReportsWithoutSaving(): void
    {
        $product = $this->staleProduct();

        $result = self::runCommand('app:resync-product-pricing', ['--dry-run' => true]);

        self::assertStringContainsString('1 would change', $result->getDisplay());
        $reloaded = $this->reload($product);
        self::assertSame('10.00', $reloaded->getCost());
        self::assertSame(5, $reloaded->getStock());
    }

    public function testResyncsAcrossBatchBoundaries(): void
    {
        $products = [$this->staleProduct(), $this->staleProduct(), $this->staleProduct()];

        $result = self::runCommand('app:resync-product-pricing', ['--batch-size' => 1]);

        self::assertStringContainsString('3 changed', $result->getDisplay());
        foreach ($products as $product) {
            self::assertSame('20.00', $this->reload($product)->getCost());
        }
    }

    public function testProductThatFailsIsLeftAsItWasAndReported(): void
    {
        $product = $this->staleProduct();
        self::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE vat_rate SET rate = -1 WHERE id = :id',
            ['id' => $product->getCategory()->getVatRate()->getId()],
        );

        $result = self::runCommand('app:resync-product-pricing');

        $this->assertCommandFailed($result);
        self::assertStringContainsString('VAT rate must not be negative', $result->getDisplay());
        self::assertStringContainsString('1 failed', $result->getDisplay());
        self::assertSame('10.00', $this->reload($product)->getCost());
    }

    public function testClearsSourceThatRanOutOfStock(): void
    {
        $product = ProductFactory::createOne();
        $source = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);
        self::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE supplier_product SET stock = 0 WHERE id = :id',
            ['id' => $source->getId()],
        );
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $result = self::runCommand('app:resync-product-pricing', ['-v' => true], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        self::assertStringContainsString('source none, stock 0', $result->getDisplay());
        $reloaded = $this->reload($product);
        self::assertNull($reloaded->getActiveProductSource());
        self::assertSame(0, $reloaded->getStock());
        self::assertSame('10.00', $reloaded->getCost());
    }

    /**
     * A product priced from its source at cost 10.00 and stock 5, whose source then changed in the
     * database without the product following, as simulated changes did before #106.
     */
    private function staleProduct(): Product
    {
        $category = CategoryFactory::createOne(['defaultMarkup' => '25.000', 'priceModel' => PriceModel::DEFAULT]);
        $product = ProductFactory::createOne(['category' => $category]);
        $source = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);

        self::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE supplier_product SET cost = :cost, stock = :stock WHERE id = :id',
            ['cost' => '20.00', 'stock' => 40, 'id' => $source->getId()],
        );
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        return $product;
    }

    private function reload(Product $product): Product
    {
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        return ProductFactory::repository()->findOrFail($product->getId());
    }
}
