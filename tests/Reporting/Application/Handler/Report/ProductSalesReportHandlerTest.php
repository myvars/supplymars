<?php

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Reporting\Application\Handler\Report\ProductSalesReportHandler;
use App\Reporting\Application\Report\ProductSalesReportCriteria;
use App\Reporting\Domain\Metric\SalesType;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductSalesFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ProductSalesReportHandlerTest extends KernelTestCase
{
    use Factories;

    private ProductSalesReportHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(ProductSalesReportHandler::class);
    }

    public function testInvokeReturnsResultWithExpectedKeys(): void
    {
        $criteria = new ProductSalesReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertArrayHasKey('type', $result->payload);
        self::assertArrayHasKey('sales', $result->payload);
        self::assertArrayHasKey('summary', $result->payload);
        self::assertArrayHasKey('productSalesChart', $result->payload);
    }

    public function testInvokeWithNoFiltersReturnsSalesTypeAll(): void
    {
        $criteria = new ProductSalesReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertSame(SalesType::ALL->value, $result->payload['type']);
    }

    public function testInvokeWithMultipleFiltersReturnsNulls(): void
    {
        $product = ProductFactory::createOne();
        $category = CategoryFactory::createOne();

        $criteria = new ProductSalesReportCriteria();
        $criteria->productId = $product->getId();
        $criteria->categoryId = $category->getId();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertNull($result->payload['type']);
        self::assertNull($result->payload['sales']);
        self::assertNull($result->payload['summary']);
        self::assertNull($result->payload['productSalesChart']);
    }

    public function testInvokeWithProductIdReturnsSalesArray(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        ProductSalesFactory::createOne([
            'product' => $product,
            'dateString' => $today,
        ]);

        $criteria = new ProductSalesReportCriteria();
        $criteria->productId = $product->getId();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertSame(SalesType::PRODUCT->value, $result->payload['type']);
        self::assertNull($result->payload['sales']);
    }

    public function testInvokeWithCategoryIdReturnsNullSales(): void
    {
        $category = CategoryFactory::createOne();

        $criteria = new ProductSalesReportCriteria();
        $criteria->categoryId = $category->getId();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertSame(SalesType::CATEGORY->value, $result->payload['type']);
        self::assertNull($result->payload['sales']);
    }
}
