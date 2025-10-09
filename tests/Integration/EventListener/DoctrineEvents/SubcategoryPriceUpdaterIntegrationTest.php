<?php

namespace App\Tests\Integration\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Enum\PriceModel;
use App\Factory\SubcategoryFactory;
use App\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SubcategoryPriceUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSubcategoryProductsRecalculateWhenDefaultMarkupChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change default markup on subcategory
        $product->getSubcategory()->setDefaultMarkup('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('132.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testNoUpdateWhenDefaultMarkupChangesOnDifferentSubcategory(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        $subcategory = SubcategoryFactory::createOne([
            'category' => $product->getCategory(),
            'defaultMarkup' => '0.000',
        ]);

        // Change default markup on different subcategory
        $subcategory->setDefaultMarkup('10.000');

        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testSubcategoryProductsSkipWhenDefaultMarkupChangesAndProductMarkupSet(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

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
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change price model on subcategory
        $product->getSubcategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.99', $updatedProduct->getSellPriceIncVat());
    }

    public function testSubcategoryProductsSkipWhenPriceModelChangesAndProductPriceModelSet(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

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