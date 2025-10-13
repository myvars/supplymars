<?php

namespace App\Tests\Integration\Service\Product;

use App\Service\Product\ProductPriceCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\SubcategoryFactory;
use tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ProductPriceCalculatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProductPriceCalculator $productPriceCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->productPriceCalculator = static::getContainer()->get(ProductPriceCalculator::class);
    }

    public function testRecalculatePrice(): void
    {
        $vatRate = VatRateFactory::createOne(['rate' => '20.00']);
        $category = CategoryFactory::createOne(['vatRate' => $vatRate]);
        $product = ProductFactory::createOne([
            'category' => $category,
            'cost' => '100.00',
            'defaultMarkup' => '15.000',
            'priceModel' => PriceModel::PRETTY_99
        ]);

        $this->productPriceCalculator->recalculatePrice($product);

        $this->assertSame('100.00', $product->getCost());
        $this->assertSame('15.000', $product->getDefaultMarkup());
        $this->assertSame(PriceModel::PRETTY_99, $product->getPriceModel());
        $this->assertSame('15.830', $product->getMarkup());
        $this->assertSame('138.99', $product->getSellPriceIncVat());
        $this->assertSame('115.83', $product->getSellPrice());
        $this->assertSame('PRODUCT', $product->getActiveMarkupTarget());
        $this->assertSame('PRODUCT', $product->getActivePriceModelTarget());
    }

    public function testRecalculatePriceWithCategoryMarkup(): void
    {
        $vatRate = VatRateFactory::createOne(['rate' => '20.00']);
        $category = CategoryFactory::createOne([
            'defaultMarkup' => '10.000',
            'priceModel' => PriceModel::PRETTY_99,
            'vatRate' => $vatRate
        ]);
        $product = ProductFactory::createOne([
            'category' => $category,
            'cost' => '100.00',
        ]);

        $this->productPriceCalculator->recalculatePrice($product);

        $this->assertSame('100.00', $product->getCost());
        $this->assertSame('10.000', $category->getDefaultMarkup());
        $this->assertSame('10.830', $product->getMarkup());
        $this->assertSame('132.99', $product->getSellPriceIncVat());
        $this->assertSame('110.83', $product->getSellPrice());
        $this->assertSame('CATEGORY', $product->getActiveMarkupTarget());
        $this->assertSame('CATEGORY', $product->getActivePriceModelTarget());
    }

    public function testRecalculatePriceWithSubcategoryMarkup(): void
    {
        $vatRate = VatRateFactory::createOne(['rate' => '20.00']);
        $category = CategoryFactory::createOne(['vatRate' => $vatRate]);
        $subcategory = SubcategoryFactory::createOne([
            'defaultMarkup' => '20.000',
            'priceModel' => PriceModel::PRETTY_99,
            'category' => $category
        ]);
        $product = ProductFactory::createOne([
            'category' => $category,
            'subcategory' => $subcategory,
            'cost' => '100.00',
        ]);

        $this->productPriceCalculator->recalculatePrice($product);

        $this->assertSame('100.00', $product->getCost());
        $this->assertSame('20.000', $subcategory->getDefaultMarkup());
        $this->assertSame('20.830', $product->getMarkup());
        $this->assertSame('144.99', $product->getSellPriceIncVat());
        $this->assertSame('120.83', $product->getSellPrice());
        $this->assertSame('SUBCATEGORY', $product->getActiveMarkupTarget());
        $this->assertSame('SUBCATEGORY', $product->getActivePriceModelTarget());
    }

    public function testRecalculatePriceFromArray(): void
    {
        $products = ProductFactory::createMany(2);

        $this->productPriceCalculator->recalculatePriceFromArray($products);

        foreach ($products as $product) {
            $this->assertNotNull($product->getMarkup());
            $this->assertNotNull($product->getSellPrice());
            $this->assertNotNull($product->getSellPriceIncVat());
        }
    }
}
