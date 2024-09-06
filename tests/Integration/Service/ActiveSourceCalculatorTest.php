<?php

namespace App\Tests\Integration\Service;


use App\Factory\ProductFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierProductFactory;
use App\Service\Product\ActiveSourceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ActiveSourceCalculatorTest extends KernelTestCase
{
    use Factories;
    private EntityManagerInterface $entityManager;

    private ActiveSourceCalculator $activeSourceCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->activeSourceCalculator = static::getContainer()->get(ActiveSourceCalculator::class);
    }

    public function testRecalculateActiveSource(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true])->_real();
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'cost' => 100,
            'stock' => 10,
            'isActive' => true,
        ])->_real();
        $product = ProductFactory::createOne(['isActive' => true])->_real();
        $product->addSupplierProduct($supplierProduct);

        $this->activeSourceCalculator->recalculateActiveSource($product);
        $this->assertEquals($supplierProduct, $product->getActiveProductSource());
    }
}