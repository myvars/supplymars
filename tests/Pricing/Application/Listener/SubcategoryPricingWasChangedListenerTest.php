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

final class SubcategoryPricingWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRecalculatesWhenSubcategoryMarkupChangesForActiveTarget(): void
    {
        $product = $this->createSourcedProduct();
        $subcategory = $product->getSubcategory();

        $subcategory->changePricing(
            defaultMarkup: '50.000',
            priceModel: $subcategory->getPriceModel(),
            isActive: $subcategory->isActive()
        );
        $this->em->flush();

        // Subcategory markup 50% now applies, VAT 20%: 20.00 × 1.50 × 1.20 = 36.00
        $reloaded = $this->reload($product);
        self::assertSame('30.00', $reloaded->getSellPrice());
        self::assertSame('36.00', $reloaded->getSellPriceIncVat());
    }

    public function testRecalculatesWhenSubcategoryPriceModelChangesForActiveTarget(): void
    {
        $product = $this->createSourcedProduct();
        $subcategory = $product->getSubcategory();

        $subcategory->changePricing(
            defaultMarkup: $subcategory->getDefaultMarkup(),
            priceModel: PriceModel::PRETTY_99,
            isActive: $subcategory->isActive()
        );
        $this->em->flush();

        // Category markup 5%, VAT 20%: 20.00 × 1.05 × 1.20 = 25.20, which PRETTY_99 makes 25.99
        self::assertSame('25.99', $this->reload($product)->getSellPriceIncVat());
    }

    /**
     * Subcategory has no markup or price model of its own, so the category's 5% and DEFAULT apply:
     * 20.00 × 1.05 × 1.20 = 25.20.
     */
    private function createSourcedProduct(): Product
    {
        $category = CategoryFactory::createOne(['defaultMarkup' => '5.000', 'priceModel' => PriceModel::DEFAULT]);
        $product = ProductFactory::createOne(['category' => $category]);
        SupplierProductFactory::createOne(['product' => $product, 'cost' => '20.00', 'stock' => 5]);
        self::assertSame('25.20', $product->getSellPriceIncVat());

        return $product;
    }

    private function reload(Product $product): Product
    {
        $this->em->clear();

        return ProductFactory::repository()->findOrFail($product->getId());
    }
}
