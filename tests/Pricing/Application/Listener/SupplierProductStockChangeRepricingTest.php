<?php

declare(strict_types=1);

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * updateStock() and updateCost() are what the stock commands call; flushing them must reprice the product.
 */
final class SupplierProductStockChangeRepricingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testStockChangeThatEmptiesActiveSourceMovesToNextSource(): void
    {
        $product = ProductFactory::createOne();
        $cheapest = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);
        $next = SupplierProductFactory::createOne(['product' => $product, 'cost' => '12.00', 'stock' => 7]);
        self::assertSame($cheapest->getId(), $product->getActiveProductSource()?->getId());

        $cheapest->updateStock(0);
        $this->em->flush();

        $reloaded = $this->reload($product);
        self::assertSame($next->getId(), $reloaded->getActiveProductSource()?->getId());
        self::assertSame(7, $reloaded->getStock());
        self::assertSame('12.00', $reloaded->getCost());
    }

    public function testStockChangeThatEmptiesOnlySourceClearsIt(): void
    {
        $product = ProductFactory::createOne();
        $source = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);

        $source->updateStock(0);

        $this->em->flush();

        $reloaded = $this->reload($product);
        self::assertNull($reloaded->getActiveProductSource());
        self::assertSame(0, $reloaded->getStock());
    }

    public function testCheaperSourceBackInStockBecomesActive(): void
    {
        $product = ProductFactory::createOne();
        $cheaper = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 0]);
        $dearer = SupplierProductFactory::createOne(['product' => $product, 'cost' => '12.00', 'stock' => 7]);
        self::assertSame($dearer->getId(), $product->getActiveProductSource()?->getId());

        $cheaper->updateStock(20);
        $this->em->flush();

        $reloaded = $this->reload($product);
        self::assertSame($cheaper->getId(), $reloaded->getActiveProductSource()?->getId());
        self::assertSame(20, $reloaded->getStock());
        self::assertSame('10.00', $reloaded->getCost());
    }

    public function testCostChangeOnActiveSourceRepricesProduct(): void
    {
        // Markup 25%, VAT 20%, no rounding: 20.00 × 1.25 × 1.20 = 30.00
        $category = CategoryFactory::createOne(['defaultMarkup' => '25.000', 'priceModel' => PriceModel::DEFAULT]);
        $product = ProductFactory::createOne(['category' => $category]);
        $source = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);
        self::assertSame('15.00', $product->getSellPriceIncVat());

        $source->updateCost('20.00');
        $this->em->flush();

        $reloaded = $this->reload($product);
        self::assertSame('20.00', $reloaded->getCost());
        self::assertSame('25.00', $reloaded->getSellPrice());
        self::assertSame('30.00', $reloaded->getSellPriceIncVat());
    }

    private function reload(Product $product): Product
    {
        $this->em->clear();

        return ProductFactory::repository()->findOrFail($product->getId());
    }
}
