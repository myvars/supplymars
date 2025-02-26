<?php

namespace App\Tests\Integration\DTO\SearchDto;

use App\DTO\SearchDto\CustomerSearchDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerSearchDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCustomerSearchDto(): void
    {
        $dto = new CustomerSearchDto();
        $dto->setSort('fullName');
        $dto->setSortDirection('ASC');
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setPage(1);
        $dto->setLimit(5);

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }
}