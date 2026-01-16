<?php

namespace App\Tests\Pricing\Integration;

use App\Catalog\Domain\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\SupplierFactory;
use tests\Shared\Factory\SupplierProductFactory;
use Zenstruck\Foundry\Test\Factories;

class SupplierStockUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProductsRecalculateWhenSupplierChangesToInactive(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change supplier status to inactive
        $supplierProduct->getSupplier()->setIsActive(false);
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertNull($updatedProduct->getActiveProductSource());
    }

    public function testProductsRecalculateWhenSupplierChangesToActive(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();
        $supplierProduct->getSupplier()->setIsActive(false);
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertNull($updatedProduct->getActiveProductSource());

        // Change supplier status to inactive
        $supplierProduct->getSupplier()->setIsActive(true);
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals($supplierProduct, $updatedProduct->getActiveProductSource());
    }

    public function testNoUpdateWhenDifferentSupplierChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change different supplier status
        $supplier = SupplierFactory::createOne();
        $supplier->setIsActive(false);

        $this->em->flush();

        $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals($supplierProduct, $product->getActiveProductSource());
    }
}
