<?php

namespace App\Tests\Integration\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Factory\SupplierFactory;
use App\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SupplierStockUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProductsRecalculateWhenSupplierChangesToInactive(): void
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $product = $supplierProduct->getProduct();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change supplier status to inactive
        $supplierProduct->getSupplier()->setIsActive(false);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertNull($updatedProduct->getActiveProductSource());
    }

    public function testProductsRecalculateWhenSupplierChangesToActive(): void
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $product = $supplierProduct->getProduct();
        $supplierProduct->getSupplier()->setIsActive(false);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertNull($updatedProduct->getActiveProductSource());

        // Change supplier status to inactive
        $supplierProduct->getSupplier()->setIsActive(true);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals($supplierProduct, $updatedProduct->getActiveProductSource());
    }

    public function testNoUpdateWhenDifferentSupplierChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $product = $supplierProduct->getProduct();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change different supplier status
        $supplier = SupplierFactory::createOne()->_real();
        $supplier->setIsActive(false);

        $this->entityManager->flush();

        $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals($supplierProduct, $product->getActiveProductSource());
    }
}