<?php

declare(strict_types=1);

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CategoryPricingWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRecalculatesActiveProductsWhenVatRateChanges(): void
    {
        $category = CategoryFactory::createOne(['defaultMarkup' => '25.000', 'priceModel' => PriceModel::DEFAULT]);
        $product = ProductFactory::createOne(['category' => $category]);
        SupplierProductFactory::createOne(['product' => $product, 'cost' => '20.00', 'stock' => 5]);
        self::assertSame('30.00', $product->getSellPriceIncVat());

        $newVat = VatRateFactory::createOne(['rate' => '25.00']);

        $category->changePricing(
            vatRate: $newVat,
            defaultMarkup: $category->getDefaultMarkup(),
            priceModel: $category->getPriceModel(),
            isActive: $category->isActive()
        );
        $this->em->flush();

        // Markup 25%, VAT 25%, no rounding: 20.00 × 1.25 × 1.25 = 31.25
        $reloaded = $this->reload($product);
        self::assertSame('25.00', $reloaded->getSellPrice());
        self::assertSame('31.25', $reloaded->getSellPriceIncVat());
    }

    public function testRecalculatesWhenCategoryMarkupAndPriceModelAreActiveTargets(): void
    {
        $category = CategoryFactory::createOne(['defaultMarkup' => '25.000', 'priceModel' => PriceModel::DEFAULT]);
        $product = ProductFactory::createOne(['category' => $category]);
        SupplierProductFactory::createOne(['product' => $product, 'cost' => '20.00', 'stock' => 5]);
        self::assertSame('CATEGORY', $product->getActiveMarkupTarget());
        self::assertSame('CATEGORY', $product->getActivePriceModelTarget());
        self::assertSame('30.00', $product->getSellPriceIncVat());

        $category->changePricing(
            vatRate: $category->getVatRate(),
            defaultMarkup: '50.000',
            priceModel: PriceModel::PRETTY_99,
            isActive: $category->isActive()
        );
        $this->em->flush();

        // Markup 50%, VAT 20%: 20.00 × 1.50 × 1.20 = 36.00, which PRETTY_99 makes 36.99
        $reloaded = $this->reload($product);
        self::assertSame('36.99', $reloaded->getSellPriceIncVat());
    }

    private function reload(Product $product): Product
    {
        $this->em->clear();

        return ProductFactory::repository()->findOrFail($product->getId());
    }
}
