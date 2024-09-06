<?php

namespace App\Tests\Integration\EventListener;

use App\Entity\Product;
use App\Factory\SupplierProductFactory;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use App\Tests\Utilities\TestProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SupplierProductStockUpdaterTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;

    private TestProduct $testProduct;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->testProduct = new TestProduct(
            $this->entityManager,
            self::getContainer()->get(ActiveSourceCalculator::class),
            self::getContainer()->get(ProductPriceCalculator::class)
        );
    }

    public function testProductsRecalculateWhenSupplierProductChanges(): void
    {
        $product = $this->testProduct->create();
        $supplierProduct = $product->getSupplierProducts()->first();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change supplier status to inactive
        $supplierProduct->setIsActive(false);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertNull($updatedProduct->getActiveProductSource());
    }

    public function testNoUpdateWhenDifferentSupplierChanges(): void
    {
        $product = $this->testProduct->create();
        $supplierProduct = $product->getSupplierProducts()->first();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change different supplier status
        $supplierProduct2 = SupplierProductFactory::createOne(['isActive' => true])->_real();
        $supplierProduct2->setIsActive(false);

        $this->entityManager->flush();

        $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals($supplierProduct, $product->getActiveProductSource());
    }
}