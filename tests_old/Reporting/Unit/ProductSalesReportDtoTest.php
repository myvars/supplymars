<?php

namespace App\Tests\Reporting\Unit;

use App\Reporting\Application\Report\ProductSalesReportCriteria;
use App\Reporting\Domain\Metric\ProductSalesMetric;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use PHPUnit\Framework\TestCase;

class ProductSalesReportDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $dto = new ProductSalesReportCriteria();

        $this->assertSame(ProductSalesMetric::QTY, $dto->getSort());
        $this->assertSame('desc', $dto->getSortDirection());
        $this->assertSame(SalesDuration::LAST_30, $dto->getDuration());
    }

    public function testSetSort(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setSort(ProductSalesMetric::VALUE->value);

        $this->assertSame(ProductSalesMetric::VALUE, $dto->getSort());
    }

    public function testInvalidSetSort(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setSort('INVALID_SORT');

        $this->assertSame(ProductSalesMetric::default(), $dto->getSort());
    }

    public function testSetSortDirection(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setSortDirection('ASC');

        $this->assertSame('asc', $dto->getSortDirection());
    }

    public function testInvalidSetSortDirection(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setSortDirection('INVALID_DIRECTION');

        $this->assertSame('desc', $dto->getSortDirection());
    }

    public function testSetDuration(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setDuration(SalesDuration::LAST_7->value);

        $this->assertSame(SalesDuration::LAST_7, $dto->getDuration());
    }

    public function testInvalidSetDuration(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setDuration('INVALID_DURATION');

        $this->assertSame(SalesDuration::default(), $dto->getDuration());
    }

    public function testSetProductId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setProductId(123);

        $this->assertSame(123, $dto->getProductId());
    }

    public function testSetCategoryId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setCategoryId(456);

        $this->assertSame(456, $dto->getCategoryId());
    }

    public function testSetSubcategoryId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setSubcategoryId(789);

        $this->assertSame(789, $dto->getSubcategoryId());
    }

    public function testSetManufacturerId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setManufacturerId(101);

        $this->assertSame(101, $dto->getManufacturerId());
    }

    public function testSetSupplierId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setSupplierId(202);

        $this->assertSame(202, $dto->getSupplierId());
    }

    public function testGetSingleSalesTypeWithNoIdentifiers(): void
    {
        $dto = new ProductSalesReportCriteria();

        $this->assertSame([
            'salesType' => SalesType::ALL,
            'salesTypeId' => 1,
        ], $dto->getSingleSalesType());
    }

    public function testGetSingleSalesTypeWithProductId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setProductId(123);

        $this->assertSame([
            'salesType' => SalesType::PRODUCT,
            'salesTypeId' => 123,
        ], $dto->getSingleSalesType());
    }

    public function testGetSingleSalesTypeWithMultipleIdentifiers(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setProductId(123);
        $dto->setCategoryId(456);

        $this->assertNull($dto->getSingleSalesType());
    }
}
