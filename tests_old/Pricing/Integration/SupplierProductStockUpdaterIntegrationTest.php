<?php

namespace App\Tests\Pricing\Integration;

use App\Catalog\Domain\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\SupplierProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SupplierProductStockUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProductsRecalculateWhenSupplierProductChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change supplier status to inactive
        $supplierProduct->setIsActive(false);
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertNull($updatedProduct->getActiveProductSource());
    }

    public function testNoUpdateWhenDifferentSupplierChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change different supplier status
        $supplierProduct2 = SupplierProductFactory::createOne(['isActive' => true]);
        $supplierProduct2->setIsActive(false);

        $this->em->flush();

        $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals($supplierProduct, $product->getActiveProductSource());
    }
}
