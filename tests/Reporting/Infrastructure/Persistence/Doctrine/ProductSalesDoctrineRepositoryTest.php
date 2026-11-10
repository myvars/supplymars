<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Product\Product;
use App\Reporting\Application\Report\ProductSalesReportCriteria;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductSalesFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductSalesDoctrineRepositoryTest extends KernelTestCase
{
    private ProductSalesDoctrineRepository $productSales;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->productSales = self::getContainer()->get(ProductSalesDoctrineRepository::class);
    }

    public function testMarginIsGrossMarginOnRevenueWeightedBySales(): void
    {
        $product = $this->createProductWithTwoSales(new \DateTime()->format('Y-m-d'));

        $criteria = new ProductSalesReportCriteria();
        $criteria->productId = $product->getId();

        $sales = $this->productSales->findByCriteria($criteria);

        self::assertCount(1, $sales);
        // (1,300 - 1,100) / 1,300; the old unweighted markup on cost was (100% + 10%) / 2 = 55%
        self::assertEqualsWithDelta(15.38, (float) $sales[0]['salesMargin'], 0.01);
    }

    public function testDailyRangeMarginIsWeightedAcrossSuppliers(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $product = $this->createProductWithTwoSales($today);

        $range = $this->productSales->findProductSalesRange($product->getId(), $today, $today);

        self::assertCount(1, $range);
        self::assertEqualsWithDelta(15.38, (float) $range[0]['salesMargin'], 0.01);
    }

    public function testMarginIsZeroWithoutSalesValue(): void
    {
        $product = ProductFactory::createOne();
        ProductSalesFactory::createOne([
            'product' => $product,
            'dateString' => new \DateTime()->format('Y-m-d'),
            'salesCost' => '100.00',
            'salesValue' => '0.00',
        ]);

        $criteria = new ProductSalesReportCriteria();
        $criteria->productId = $product->getId();

        $sales = $this->productSales->findByCriteria($criteria);

        self::assertEqualsWithDelta(0.0, (float) $sales[0]['salesMargin'], 0.001);
    }

    private function createProductWithTwoSales(string $today): Product
    {
        $product = ProductFactory::createOne();

        ProductSalesFactory::createOne([
            'product' => $product,
            'supplier' => SupplierFactory::createOne(),
            'dateString' => $today,
            'salesCost' => '100.00',
            'salesValue' => '200.00',
        ]);
        ProductSalesFactory::createOne([
            'product' => $product,
            'supplier' => SupplierFactory::createOne(),
            'dateString' => $today,
            'salesCost' => '1000.00',
            'salesValue' => '1100.00',
        ]);

        return $product;
    }
}
