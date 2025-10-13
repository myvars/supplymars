<?php

namespace App\Tests\Pricing\Integration;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\SubcategoryFactory;
use tests\Shared\Factory\SupplierProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SubcategoryPriceUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSubcategoryProductsRecalculateWhenDefaultMarkupChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change default markup on subcategory
        $product->getSubcategory()->setDefaultMarkup('10.000');
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
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

        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testSubcategoryProductsSkipWhenDefaultMarkupChangesAndProductMarkupSet(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Set default markup on product
        $product->applyDefaultMarkup('5.000');
        // Change default markup on subcategory
        $product->getSubcategory()->setDefaultMarkup('10.000');
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }

    public function testSubcategoryProductsRecalculateWhenPriceModelChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change price model on subcategory
        $product->getSubcategory()->setPriceModel(PriceModel::PRETTY_99);
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
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
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }
}
