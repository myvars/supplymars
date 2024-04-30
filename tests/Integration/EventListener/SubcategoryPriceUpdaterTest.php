<?php

namespace App\Tests\Integration\EventListener;

use App\Entity\PriceModel;
use App\Entity\Product;
use App\Factory\SubcategoryFactory;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use App\Tests\Utilities\TestProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SubcategoryPriceUpdaterTest extends KernelTestCase
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

    public function testSubcategoryProductsRecalculateWhenDefaultMarkupChanges(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change default markup on subcategory
        $product->getSubcategory()->setDefaultMarkup('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('132.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testNoUpdateWhenDefaultMarkupChangesOnDifferentSubcategory(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        $subcategory = SubcategoryFactory::createOne([
            'category' => $product->getCategory(),
            'defaultMarkup' => '0.000',
        ])->object();

        // Change default markup on different subcategory
        $subcategory->setDefaultMarkup('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testSubcategoryProductsSkipWhenDefaultMarkupChangesAndProductMarkupSet(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set default markup on product
        $product->setDefaultMarkup('5.000');
        // Change default markup on subcategory
        $product->getSubcategory()->setDefaultMarkup('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testSubcategoryProductsRecalculateWhenPriceModelChanges(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change price model on subcategory
        $product->getSubcategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.99', $updatedProduct->getSellPriceIncVat());
    }

    public function testSubcategoryProductsSkipWhenPriceModelChangesAndProductPriceModelSet(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set price model on product
        $product->setPriceModel(PriceModel::DEFAULT);
        // Change price model on subcategory
        $product->getSubcategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }
}