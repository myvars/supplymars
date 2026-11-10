<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;
use App\Tests\Shared\Factory\ProductSalesSummaryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductSalesSummaryDoctrineRepositoryTest extends KernelTestCase
{
    private ProductSalesSummaryDoctrineRepository $summaries;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->summaries = self::getContainer()->get(ProductSalesSummaryDoctrineRepository::class);
    }

    public function testMarginIsGrossMarginOnRevenue(): void
    {
        $this->createSummary('1100.00', '1300.00');

        $summary = $this->summaries->findProductSalesSummary(1, SalesType::ALL, SalesDuration::TODAY);

        self::assertNotNull($summary);
        // (1,300 - 1,100) / 1,300; markup on cost would be 18.18%
        self::assertEqualsWithDelta(15.38, (float) $summary['salesMargin'], 0.01);
    }

    public function testMarginIsZeroWithoutSalesValue(): void
    {
        $this->createSummary('100.00', '0.00');

        $summary = $this->summaries->findProductSalesSummary(1, SalesType::ALL, SalesDuration::TODAY);

        self::assertNotNull($summary);
        self::assertEqualsWithDelta(0.0, (float) $summary['salesMargin'], 0.001);
    }

    private function createSummary(string $salesCost, string $salesValue): void
    {
        ProductSalesSummaryFactory::createOne([
            'productSalesType' => ProductSalesType::create(SalesType::ALL, SalesDuration::TODAY),
            'salesId' => 1,
            'dateString' => new \DateTime()->format('Y-m-d'),
            'salesCost' => $salesCost,
            'salesValue' => $salesValue,
        ]);
    }
}
