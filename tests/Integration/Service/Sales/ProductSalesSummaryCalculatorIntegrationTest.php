<?php

namespace App\Tests\Integration\Service\Sales;

use App\Entity\ProductSalesSummary;
use App\Enum\SalesDuration;
use App\Factory\ProductSalesFactory;
use App\Factory\ProductSalesSummaryFactory;
use App\Service\Sales\ProductSalesSummaryCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductSalesSummaryCalculatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProductSalesSummaryCalculator $productSalesSummaryCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->productSalesSummaryCalculator = new ProductSalesSummaryCalculator($entityManager, $validator);
    }

    public function testProcessedSuccessfully(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        ProductSalesFactory::createOne([
            'dateString' => $date,
            'salesQty' => 10,
            'salesCost' => '500.00',
            'salesValue' => '1000.00'
        ]);

        $this->productSalesSummaryCalculator->process();

        $productSalesSummary = ProductSalesSummaryFactory::repository()->findOneBy([
            'duration' => SalesDuration::LAST_7->value,
            'dateString' => SalesDuration::LAST_7->getStartDate()
        ]);

        $this->assertInstanceOf(ProductSalesSummary::class, $productSalesSummary);

        $this->assertSame(10, $productSalesSummary->getSalesQty());
        $this->assertSame('500.00', $productSalesSummary->getSalesCost());
        $this->assertSame('1000.00', $productSalesSummary->getSalesValue());
    }
}