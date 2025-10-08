<?php

namespace App\Tests\Integration\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SupplierProductStockUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProductsRecalculateWhenSupplierProductChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $product = $supplierProduct->getProduct();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change supplier status to inactive
        $supplierProduct->setIsActive(false);
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertNull($updatedProduct->getActiveProductSource());
    }

    public function testNoUpdateWhenDifferentSupplierChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $product = $supplierProduct->getProduct();

        $this->assertEquals($supplierProduct, $product->getActiveProductSource());

        // Change different supplier status
        $supplierProduct2 = SupplierProductFactory::createOne(['isActive' => true])->_real();
        $supplierProduct2->setIsActive(false);

        $this->entityManager->flush();

        $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals($supplierProduct, $product->getActiveProductSource());
    }
}