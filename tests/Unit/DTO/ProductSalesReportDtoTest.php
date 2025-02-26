<?php

namespace App\Tests\Unit\DTO;

use App\DTO\ProductSalesReportDto;
use App\Enum\ProductSalesMetric;
use App\Enum\SalesDuration;
use App\Enum\SalesType;
use PHPUnit\Framework\TestCase;

class ProductSalesReportDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $dto = new ProductSalesReportDto();

        $this->assertSame(ProductSalesMetric::QTY, $dto->getSort());
        $this->assertSame('desc', $dto->getSortDirection());
        $this->assertSame(SalesDuration::LAST_30, $dto->getDuration());
    }

    public function testSetSort(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setSort(ProductSalesMetric::VALUE->value);

        $this->assertSame(ProductSalesMetric::VALUE, $dto->getSort());
    }

    public function testInvalidSetSort(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setSort('INVALID_SORT');

        $this->assertSame(ProductSalesMetric::default(), $dto->getSort());
    }

    public function testSetSortDirection(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setSortDirection('ASC');

        $this->assertSame('asc', $dto->getSortDirection());
    }

    public function testInvalidSetSortDirection(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setSortDirection('INVALID_DIRECTION');

        $this->assertSame('desc', $dto->getSortDirection());
    }

    public function testSetDuration(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setDuration(SalesDuration::LAST_7->value);

        $this->assertSame(SalesDuration::LAST_7, $dto->getDuration());
    }

    public function testInvalidSetDuration(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setDuration('INVALID_DURATION');

        $this->assertSame(SalesDuration::default(), $dto->getDuration());
    }

    public function testSetProductId(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setProductId(123);

        $this->assertSame(123, $dto->getProductId());
    }

    public function testSetCategoryId(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setCategoryId(456);

        $this->assertSame(456, $dto->getCategoryId());
    }

    public function testSetSubcategoryId(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setSubcategoryId(789);

        $this->assertSame(789, $dto->getSubcategoryId());
    }

    public function testSetManufacturerId(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setManufacturerId(101);

        $this->assertSame(101, $dto->getManufacturerId());
    }

    public function testSetSupplierId(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setSupplierId(202);

        $this->assertSame(202, $dto->getSupplierId());
    }

    public function testGetSingleSalesTypeWithNoIdentifiers(): void
    {
        $dto = new ProductSalesReportDto();

        $this->assertSame([
            'salesType' => SalesType::ALL,
            'salesTypeId' => 1,
        ], $dto->getSingleSalesType());
    }

    public function testGetSingleSalesTypeWithProductId(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setProductId(123);

        $this->assertSame([
            'salesType' => SalesType::PRODUCT,
            'salesTypeId' => 123,
        ], $dto->getSingleSalesType());
    }

    public function testGetSingleSalesTypeWithMultipleIdentifiers(): void
    {
        $dto = new ProductSalesReportDto();
        $dto->setProductId(123);
        $dto->setCategoryId(456);

        $this->assertNull($dto->getSingleSalesType());
    }
}