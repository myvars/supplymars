<?php

namespace App\Tests\Integration\EventListener;

use App\Entity\Product;
use App\Factory\VatRateFactory;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use App\Tests\Utilities\TestProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class VatRatePriceUpdaterTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;
    private TestProduct $testProduct;

    public function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->testProduct = new TestProduct(
            $this->entityManager,
            self::getContainer()->get(ActiveSourceCalculator::class),
            self::getContainer()->get(ProductPriceCalculator::class)
        );
    }

    public function testVatRateProductsRecalculateWhenVatRateChanges(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change vat rate on category
        $product->getCategory()->getVatRate()->setRate('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('115.50', $updatedProduct->getSellPriceIncVat());
    }

    public function testNoUpdateWhenDifferentVatRateChanges(): void
    {
        $product = $this->testProduct->create();

        $this->assertEquals('126.00', $product->getSellPriceIncVat());

        // Change different vat rate
        $vatRate = VatRateFactory::createOne(['rate' => '5.000'])->object();
        $vatRate->setRate('10.000');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('126.00', $updatedProduct->getSellPriceIncVat());
    }
}