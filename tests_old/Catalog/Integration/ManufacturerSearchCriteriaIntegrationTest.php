<?php

namespace App\Tests\Catalog\Integration;

use App\Catalog\Application\Search\ManufacturerSearchCriteria;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ManufacturerSearchCriteriaIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCustomerSearchDto(): void
    {
        $criteria = new ManufacturerSearchCriteria();
        $criteria->setSort('name');
        $criteria->setSortDirection('ASC');
        $criteria->setQuery('query');
        $criteria->setPage(1);
        $criteria->setLimit(5);

        $errors = $this->validator->validate($criteria);
        $this->assertCount(0, $errors);
    }
}
