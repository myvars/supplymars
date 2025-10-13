<?php

namespace App\Tests\Reporting\Integration;

use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\ProductSalesFactory;
use tests\Shared\Factory\SupplierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductSalesIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidProductSales(): void
    {
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();
        $productSales = ProductSalesFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'dateString' => '2023-01-01',
            'salesQty' => 100,
            'salesCost' => '500.00',
            'salesValue' => '1000.00',
        ]);

        $errors = $this->validator->validate($productSales);
        $this->assertCount(0, $errors);
    }

    public function testDateStringIsRequired(): void
    {
        $productSales = ProductSalesFactory::new()->withoutPersisting()->create(['dateString' => '']);

        $violations = $this->validator->validate($productSales);
        $this->assertSame('Date string must not be blank', $violations[0]->getMessage());
    }

    public function testInvalidSalesQty(): void
    {
        $productSales = ProductSalesFactory::new()->withoutPersisting()->create(['salesQty' => -1]);

        $violations = $this->validator->validate($productSales);
        $this->assertSame('Sales quantity must be zero or positive', $violations[0]->getMessage());
    }

    public function testSalesCostIsRequired(): void
    {
        $productSales = ProductSalesFactory::new()->withoutPersisting()->create(['salesCost' => '']);

        $violations = $this->validator->validate($productSales);
        $this->assertSame('Sales cost must not be blank', $violations[0]->getMessage());
    }

    public function testSalesValueIsRequired(): void
    {
        $productSales = ProductSalesFactory::new()->withoutPersisting()->create(['salesValue' => '']);

        $violations = $this->validator->validate($productSales);
        $this->assertSame('Sales value must not be blank', $violations[0]->getMessage());
    }

    public function testProductSalesPersistence(): void
    {
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();
        ProductSalesFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'dateString' => '2023-01-01',
            'salesQty' => 100,
            'salesCost' => '500.00',
            'salesValue' => '1000.00',
        ]);

        $persistedProductSales = ProductSalesFactory::repository()->findOneBy([
            'product' => $product,
            'supplier' => $supplier,
            'dateString' => '2023-01-01',
        ]);

        $this->assertEquals(100, $persistedProductSales->getSalesQty());
    }
}
