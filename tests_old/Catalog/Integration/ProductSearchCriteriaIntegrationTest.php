<?php

namespace App\Tests\Catalog\Integration;

use App\Catalog\Application\Search\ProductSearchCriteria;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductSearchCriteriaIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidProductSearchDto(): void
    {
        $criteria = new ProductSearchCriteria();
        $criteria->setSort('name');
        $criteria->setSortDirection('ASC');
        $criteria->mfrPartNumber = 'ABC123';
        $criteria->categoryId = 123;
        $criteria->subcategoryId = 456;
        $criteria->manufacturerId = 789;
        $criteria->inStock = true;

        $errors = $this->validator->validate($criteria);
        $this->assertCount(0, $errors);
    }

    public function testInvalidCategoryId(): void
    {
        $criteria = new ProductSearchCriteria();
        $criteria->categoryId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Category Id', $errors[0]->getMessage());
    }

    public function testInvalidSubcategoryId(): void
    {
        $criteria = new ProductSearchCriteria();
        $criteria->subcategoryId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Subcategory Id', $errors[0]->getMessage());
    }

    public function testInvalidManufacturerId(): void
    {
        $criteria = new ProductSearchCriteria();
        $criteria->manufacturerId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Manufacturer Id', $errors[0]->getMessage());
    }
}
