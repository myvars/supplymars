<?php

namespace App\Tests\Integration\EventListener;


use App\Entity\PriceModel;
use App\Entity\Product;
use App\Factory\CategoryFactory;
use App\Factory\VatRateFactory;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use App\Tests\Utilities\TestProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CategoryPriceUpdaterTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;
    private TestProduct $testProduct;

    public function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->testProduct = new TestProduct(
            $this->entityManager,
            self::getContainer()->get(ActiveSourceCalculator::class),
            self::getContainer()->get(ProductPriceCalculator::class)
        );
    }

    public function testCategoryProductsRecalculateWhenVatRateChanges(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change vat rate on category
        $vatRate = VatRateFactory::createOne(['rate' => '10.000'])->object();
        $product->getCategory()->setVatRate($vatRate);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('115.50', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsRecalculateWhenDefaultMarkupChanges(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change default markup on category
        $product->getCategory()->setDefaultMarkup('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('132.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testNoUpdateWhenDefaultMarkupChangesonDifferentCategory(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change default markup on different category
        $category = CategoryFactory::createOne([
            'defaultMarkup' => '5.000',
            'isActive' => true
        ])->object();
        $category->setDefaultMarkup('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsSkipWhenDefaultMarkupChangesAndProductMarkupSet(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set product markup
        $product->setDefaultMarkup('5.000');
        // Change default markup on category
        $product->getCategory()->setDefaultMarkup('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsSkipWhenDefaultMarkupChangesAndSubcategoryMarkupSet(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set subcategory markup
        $product->getSubcategory()->setDefaultMarkup('5.000');
        // Change default markup on category
        $product->getCategory()->setDefaultMarkup('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsRecalculateWhenPriceModelChanges(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change price model on category
        $product->getCategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.99', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsSkipWhenPriceModelChangesAndProductPriceModelSet(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set product price model
        $product->setPriceModel(PriceModel::DEFAULT);
        // Change price model on category
        $product->getCategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsSkipWhenPriceModelChangesAndSubcategoryPriceModelSet(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set subcategory price model
        $product->getSubcategory()->setPriceModel(PriceModel::DEFAULT);
        // Change price model on category
        $product->getCategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }
}