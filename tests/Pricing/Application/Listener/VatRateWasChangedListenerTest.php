<?php

declare(strict_types=1);

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class VatRateWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRecalculatesActiveProductsWhenVatRateChanges(): void
    {
        $vatRate = VatRateFactory::new()->withStandardRate()->create();
        $product = $this->createSourcedProduct($vatRate, '20.00');
        // Markup 25%, VAT 20%: 20.00 × 1.25 × 1.20 = 30.00
        self::assertSame('30.00', $product->getSellPriceIncVat());

        $vatRate->update(name: $vatRate->getName(), rate: '25.00');
        $this->em->flush();

        // 20.00 × 1.25 × 1.25 = 31.25
        $reloaded = $this->reload($product);
        self::assertSame('25.00', $reloaded->getSellPrice());
        self::assertSame('31.25', $reloaded->getSellPriceIncVat());
    }

    public function testRecalculatesAcrossMultipleCategories(): void
    {
        $vatRate = VatRateFactory::new()->withStandardRate()->create();
        $productA = $this->createSourcedProduct($vatRate, '20.00');
        $productB = $this->createSourcedProduct($vatRate, '40.00');
        self::assertNotSame($productA->getCategory()->getId(), $productB->getCategory()->getId());

        $vatRate->update($vatRate->getName(), '15.00');
        $this->em->flush();

        $this->em->clear();
        // Markup 25%, VAT 15%: 20.00 × 1.25 × 1.15 = 28.75
        self::assertSame('28.75', ProductFactory::repository()->findOrFail($productA->getId())->getSellPriceIncVat());
        // 40.00 × 1.25 × 1.15 = 57.50
        self::assertSame('57.50', ProductFactory::repository()->findOrFail($productB->getId())->getSellPriceIncVat());
    }

    /**
     * A product in its own category: markup 25%, no rounding.
     *
     * @param numeric-string $cost
     */
    private function createSourcedProduct(VatRate $vatRate, string $cost): Product
    {
        $category = CategoryFactory::createOne([
            'vatRate' => $vatRate,
            'defaultMarkup' => '25.000',
            'priceModel' => PriceModel::DEFAULT,
        ]);
        $product = ProductFactory::createOne(['category' => $category]);
        SupplierProductFactory::createOne(['product' => $product, 'cost' => $cost, 'stock' => 5]);

        return $product;
    }

    private function reload(Product $product): Product
    {
        $this->em->clear();

        return ProductFactory::repository()->findOrFail($product->getId());
    }
}
