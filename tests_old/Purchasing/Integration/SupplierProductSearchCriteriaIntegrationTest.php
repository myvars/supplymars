<?php

namespace App\Tests\Purchasing\Integration;

use App\Purchasing\Application\Search\SupplierProductSearchCriteria;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SupplierProductSearchCriteriaIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSupplierProductSearchDto(): void
    {
        $criteria = new SupplierProductSearchCriteria();
        $criteria->setSort('name');
        $criteria->setSortDirection('ASC');
        $criteria->supplierId = 123;
        $criteria->productCode = 'ABC123';
        $criteria->supplierCategoryId = 456;
        $criteria->supplierSubcategoryId = 789;
        $criteria->supplierManufacturerId = 101;
        $criteria->inStock = true;
        $criteria->isActive = true;

        $errors = $this->validator->validate($criteria);
        $this->assertCount(0, $errors);
    }

    public function testInvalidSupplierId(): void
    {
        $criteria = new SupplierProductSearchCriteria();
        $criteria->supplierId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Supplier Id', $errors[0]->getMessage());
    }

    public function testInvalidSupplierCategoryId(): void
    {
        $criteria = new SupplierProductSearchCriteria();
        $criteria->supplierCategoryId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Supplier category Id', $errors[0]->getMessage());
    }

    public function testInvalidSupplierSubcategoryId(): void
    {
        $criteria = new SupplierProductSearchCriteria();
        $criteria->supplierSubcategoryId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Supplier Subcategory Id', $errors[0]->getMessage());
    }

    public function testInvalidSupplierManufacturerId(): void
    {
        $criteria = new SupplierProductSearchCriteria();
        $criteria->supplierManufacturerId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Supplier Manufacturer Id', $errors[0]->getMessage());
    }
}
