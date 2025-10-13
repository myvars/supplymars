<?php

namespace App\Tests\Pricing\Integration;

use App\Catalog\Domain\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class VatRatePriceUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testVatRateProductsRecalculateWhenVatRateChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change vat rate on category
        $product->getCategory()->getVatRate()->changeRate('10.000');
        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('115.50', $updatedProduct->getSellPriceIncVat());
    }

    public function testNoUpdateWhenDifferentVatRateChanges(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['cost' => "100.00"]);
        $product = $supplierProduct->getProduct();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change different vat rate
        $vatRate = VatRateFactory::createOne(['rate' => '5.000']);
        $vatRate->changeRate('10.000');

        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }
}
