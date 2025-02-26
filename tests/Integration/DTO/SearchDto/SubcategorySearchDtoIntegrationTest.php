<?php

namespace App\Tests\Integration\DTO\SearchDto;

use App\DTO\SearchDto\SubcategorySearchDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubcategorySearchDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSubcategorySearchDto(): void
    {
        $dto = new SubcategorySearchDto();
        $dto->setSort('name');
        $dto->setSortDirection('ASC');
        $dto->setCategoryId(123);
        $dto->setPriceModel('PRETTY_99');
        $dto->setManagerId(456);

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidCategoryId(): void
    {
        $dto = new SubcategorySearchDto();
        $dto->setCategoryId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Category Id', $errors[0]->getMessage());
    }

    public function testInvalidManagerId(): void
    {
        $dto = new SubcategorySearchDto();
        $dto->setManagerId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Manager Id', $errors[0]->getMessage());
    }
}