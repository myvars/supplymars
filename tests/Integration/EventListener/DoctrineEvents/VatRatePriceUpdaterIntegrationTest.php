<?php

namespace App\Tests\Integration\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Factory\SupplierProductFactory;
use App\Factory\VatRateFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class VatRatePriceUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testVatRateProductsRecalculateWhenVatRateChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"])->_real();
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change vat rate on category
        $product->getCategory()->getVatRate()->setRate('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('115.50', $updatedProduct->getSellPriceIncVat());
    }

    public function testNoUpdateWhenDifferentVatRateChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"])->_real();
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change different vat rate
        $vatRate = VatRateFactory::createOne(['rate' => '5.000'])->_real();
        $vatRate->setRate('10.000');

        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }
}