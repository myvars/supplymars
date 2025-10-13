<?php

namespace App\Tests\Reporting\Integration;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use tests\Shared\Factory\ProductSalesSummaryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductSalesSummaryIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidProductSalesSummary(): void
    {
        $productSalesType = ProductSalesType::create(
            SalesType::PRODUCT,
            SalesDuration::LAST_7
        );
        $productSalesSummary = ProductSalesSummaryFactory::createOne([
            'productSalesType' => $productSalesType,
            'salesId' => 1,
            'dateString' => '2023-01-01',
            'salesQty' => 100,
            'salesCost' => '500.00',
            'salesValue' => '1000.00',
        ]);

        $errors = $this->validator->validate($productSalesSummary);
        $this->assertCount(0, $errors);
    }

    public function testDateStringIsRequired(): void
    {
        $productSalesSummary = ProductSalesSummaryFactory::new()->withoutPersisting()->create(['dateString' => '']);

        $violations = $this->validator->validate($productSalesSummary);
        $this->assertSame('Date string must not be blank', $violations[0]->getMessage());
    }

    public function testInvalidSalesQty(): void
    {
        $productSalesSummary = ProductSalesSummaryFactory::new()->withoutPersisting()->create(['salesQty' => -1]);

        $violations = $this->validator->validate($productSalesSummary);
        $this->assertSame('Sales quantity must be zero or positive', $violations[0]->getMessage());
    }

    public function testSalesCostIsRequired(): void
    {
        $productSalesSummary = ProductSalesSummaryFactory::new()->withoutPersisting()->create(['salesCost' => '']);

        $violations = $this->validator->validate($productSalesSummary);
        $this->assertSame('Sales cost must not be blank', $violations[0]->getMessage());
    }

    public function testSalesValueIsRequired(): void
    {
        $productSalesSummary = ProductSalesSummaryFactory::new()->withoutPersisting()->create(['salesValue' => '']);

        $violations = $this->validator->validate($productSalesSummary);
        $this->assertSame('Sales value must not be blank', $violations[0]->getMessage());
    }

    public function testProductSalesSummaryPersistence(): void
    {
        $productSalesType = ProductSalesType::create(
            SalesType::PRODUCT,
            SalesDuration::LAST_7
        );
        ProductSalesSummaryFactory::createOne([
            'productSalesType' => $productSalesType,
            'salesId' => 1,
            'dateString' => '2023-01-01',
            'salesQty' => 100,
            'salesCost' => '500.00',
            'salesValue' => '1000.00',
        ]);

        $persistedProductSalesSummary = ProductSalesSummaryFactory::repository()->findOneBy([
            'salesId' => 1,
            'salesType' => SalesType::PRODUCT->value,
            'duration' => SalesDuration::LAST_7->value,
        ]);

        $this->assertEquals(100, $persistedProductSalesSummary->getSalesQty());
    }
}
