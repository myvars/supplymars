<?php

namespace App\Tests\Integration\DTO\SearchDto;

use App\DTO\SearchDto\ProductSearchDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductSearchDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidProductSearchDto(): void
    {
        $dto = new ProductSearchDto();
        $dto->setSort('name');
        $dto->setSortDirection('ASC');
        $dto->setMfrPartNumber('ABC123');
        $dto->setCategoryId(123);
        $dto->setSubcategoryId(456);
        $dto->setManufacturerId(789);
        $dto->setInStock(true);

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidCategoryId(): void
    {
        $dto = new ProductSearchDto();
        $dto->setCategoryId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Category Id', $errors[0]->getMessage());
    }

    public function testInvalidSubcategoryId(): void
    {
        $dto = new ProductSearchDto();
        $dto->setSubcategoryId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Subcategory Id', $errors[0]->getMessage());
    }

    public function testInvalidManufacturerId(): void
    {
        $dto = new ProductSearchDto();
        $dto->setManufacturerId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Manufacturer Id', $errors[0]->getMessage());
    }
}