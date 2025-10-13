<?php

namespace App\Tests\Catalog\Integration;

use App\Catalog\Application\Search\CategorySearchCriteria;
use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategorySearchCriteriaIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCategorySearchDto(): void
    {
        $criteria = new CategorySearchCriteria();
        $criteria->setSort('name');
        $criteria->setSortDirection('ASC');
        $criteria->priceModel = PriceModel::PRETTY_99->value;
        $criteria->managerId = 123;
        $criteria->vatRateId = 456;

        $errors = $this->validator->validate($criteria);
        $this->assertCount(0, $errors);
    }

    public function testInvalidManagerId(): void
    {
        $criteria = new CategorySearchCriteria();
        $criteria->managerId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Manager Id', $errors[0]->getMessage());
    }

    public function testInvalidVatRateId(): void
    {
        $criteria = new CategorySearchCriteria();
        $criteria->vatRateId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Vat Rate Id', $errors[0]->getMessage());
    }
}
