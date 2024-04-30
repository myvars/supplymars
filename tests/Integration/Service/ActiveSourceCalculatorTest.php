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

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->activeSourceCalculator = static::getContainer()->get(ActiveSourceCalculator::class);
    }

    public function testRecalculateActiveSource(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true])->object();
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'cost' => 100,
            'stock' => 10,
            'isActive' => true,
        ])->object();
        $product = ProductFactory::createOne(['isActive' => true])->object();
        $product->addSupplierProduct($supplierProduct);

        $this->activeSourceCalculator->recalculateActiveSource($product);
        $this->assertEquals($supplierProduct, $product->getActiveProductSource());
    }
}