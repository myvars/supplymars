<?php

namespace App\Tests\Reporting\Integration;

use App\Reporting\Application\Report\ProductSalesReportCriteria;
use App\Reporting\Domain\Metric\ProductSalesMetric;
use App\Reporting\Domain\Metric\SalesDuration;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductSalesReportDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidProductSalesReportDto(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setSort(ProductSalesMetric::VALUE->value);
        $dto->setSortDirection('ASC');
        $dto->setDuration(SalesDuration::LAST_7->value);
        $dto->setProductId(123);
        $dto->setCategoryId(456);
        $dto->setSubcategoryId(789);
        $dto->setManufacturerId(101);
        $dto->setSupplierId(202);

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidProductId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setProductId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Product Id', $errors[0]->getMessage());
    }

    public function testInvalidCategoryId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setCategoryId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Category Id', $errors[0]->getMessage());
    }

    public function testInvalidSubcategoryId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setSubcategoryId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Subcategory Id', $errors[0]->getMessage());
    }

    public function testInvalidManufacturerId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setManufacturerId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Manufacturer Id', $errors[0]->getMessage());
    }

    public function testInvalidSupplierId(): void
    {
        $dto = new ProductSalesReportCriteria();
        $dto->setSupplierId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Supplier Id', $errors[0]->getMessage());
    }
}
