<?php

namespace App\Tests\Integration\Service;

use App\Entity\PriceModel;
use App\Entity\Product;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use App\Tests\Utilities\TestProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ProductPriceCalculatorTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;
    private ProductPriceCalculator $productPriceCalculator;
    private TestProduct $testProduct;

    public function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->productPriceCalculator = self::getContainer()->get(ProductPriceCalculator::class);
        $this->testProduct = new TestProduct(
            $this->entityManager,
            self::getContainer()->get(ActiveSourceCalculator::class),
            $this->productPriceCalculator
        );
    }

    public function testRecalculatePriceUsingProduct(): void
    {
        $product = $this->testProduct->create();

        $product->setCost(100);
        $product->setDefaultMarkup('15.000');
        $product->setPriceModel(PriceModel::PRETTY_99);

        $this->productPriceCalculator->recalculatePrice($product, true);
        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());

        $this->assertEquals('115.83', $updatedProduct->getSellPrice());
        $this->assertEquals('138.99', $updatedProduct->getSellPriceIncVat());
        $this->assertEquals('15.830', $updatedProduct->getMarkup());
    }

    public function testRecalculatePriceUsingSubcategory(): void
    {
        $product = $this->testProduct->create();

        $product->setCost(100);
        $product->getSubcategory()->setPriceModel(PriceModel::PRETTY_99);
        $product->getSubcategory()->setDefaultMarkup('10.000');

        $this->productPriceCalculator->recalculatePrice($product, true);
        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());

        $this->assertEquals('110.83', $updatedProduct->getSellPrice());
        $this->assertEquals('132.99', $updatedProduct->getSellPriceIncVat());
        $this->assertEquals('10.830', $updatedProduct->getMarkup());
    }

    public function testRecalculatePriceUsingCategory(): void
    {
        $product = $this->testProduct->create();

        $product->setCost(100);
        $product->getCategory()->setPriceModel(PriceModel::PRETTY_99);
        $product->getCategory()->setDefaultMarkup('5.000');

        $this->productPriceCalculator->recalculatePrice($product, true);
        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());

        $this->assertEquals('105.83', $updatedProduct->getSellPrice());
        $this->assertEquals('126.99', $updatedProduct->getSellPriceIncVat());
        $this->assertEquals('5.830', $updatedProduct->getMarkup());
    }
}