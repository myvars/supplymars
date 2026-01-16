<?php

namespace App\Tests\Pricing\Integration;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\VatRateFactory;
use Zenstruck\Foundry\Test\Factories;

class CategoryPriceUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCategoryProductsRecalculateWhenVatRateChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => '100.00']);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change vat rate on category
        $vatRate = VatRateFactory::createOne(['rate' => '10.000']);
        $product->getCategory()->changeVatRate($vatRate);
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('115.50', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsRecalculateWhenDefaultMarkupChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => '100.00']);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change default markup on category
        $product->getCategory()->setDefaultMarkup('10.000');
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('132.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testNoUpdateWhenDefaultMarkupChangesOnDifferentCategory(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => '100.00']);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change default markup on different category
        $category = CategoryFactory::createOne(['defaultMarkup' => '5.000']);
        $category->setDefaultMarkup('10.000');

        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsSkipWhenDefaultMarkupChangesAndProductMarkupSet(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => '100.00']);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set product markup
        $product->applyDefaultMarkup('5.000');
        // Change default markup on category
        $product->getCategory()->setDefaultMarkup('10.000');
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsSkipWhenDefaultMarkupChangesAndSubcategoryMarkupSet(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => '100.00']);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set subcategory markup
        $product->getSubcategory()->setDefaultMarkup('5.000');
        // Change default markup on category
        $product->getCategory()->setDefaultMarkup('10.000');
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsRecalculateWhenPriceModelChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => '100.00']);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change price model on category
        $product->getCategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.99', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsSkipWhenPriceModelChangesAndProductPriceModelSet(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => '100.00']);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set product price model
        $product->setPriceModel(PriceModel::DEFAULT);
        // Change price model on category
        $product->getCategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testCategoryProductsSkipWhenPriceModelChangesAndSubcategoryPriceModelSet(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => '100.00']);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set subcategory price model
        $product->getSubcategory()->setPriceModel(PriceModel::DEFAULT);
        // Change price model on category
        $product->getCategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }
}
