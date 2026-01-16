<?php

namespace App\Tests\Catalog\Integration;

use App\Catalog\Application\Search\SubcategorySearchCriteria;
use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubcategorySearchCriteriaIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSubcategorySearchDto(): void
    {
        $criteria = new SubcategorySearchCriteria();
        $criteria->setSort('name');
        $criteria->setSortDirection('ASC');
        $criteria->categoryId = 123;
        $criteria->priceModel = PriceModel::PRETTY_99->value;
        $criteria->managerId = 456;

        $errors = $this->validator->validate($criteria);
        $this->assertCount(0, $errors);
    }

    public function testInvalidCategoryId(): void
    {
        $criteria = new SubcategorySearchCriteria();
        $criteria->categoryId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Category Id', $errors[0]->getMessage());
    }

    public function testInvalidManagerId(): void
    {
        $criteria = new SubcategorySearchCriteria();
        $criteria->managerId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Manager Id', $errors[0]->getMessage());
    }
}
