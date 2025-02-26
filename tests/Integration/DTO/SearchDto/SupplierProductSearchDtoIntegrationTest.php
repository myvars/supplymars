<?php

namespace App\Tests\Integration\DTO\SearchDto;

use App\DTO\SearchDto\SupplierProductSearchDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SupplierProductSearchDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSupplierProductSearchDto(): void
    {
        $dto = new SupplierProductSearchDto();
        $dto->setSort('name');
        $dto->setSortDirection('ASC');
        $dto->setSupplierId(123);
        $dto->setProductCode('ABC123');
        $dto->setSupplierCategoryId(456);
        $dto->setSupplierSubcategoryId(789);
        $dto->setSupplierManufacturerId(101);
        $dto->setInStock(true);
        $dto->setIsActive(true);

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidSupplierId(): void
    {
        $dto = new SupplierProductSearchDto();
        $dto->setSupplierId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Supplier Id', $errors[0]->getMessage());
    }

    public function testInvalidSupplierCategoryId(): void
    {
        $dto = new SupplierProductSearchDto();
        $dto->setSupplierCategoryId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Supplier category Id', $errors[0]->getMessage());
    }

    public function testInvalidSupplierSubcategoryId(): void
    {
        $dto = new SupplierProductSearchDto();
        $dto->setSupplierSubcategoryId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Supplier Subcategory Id', $errors[0]->getMessage());
    }

    public function testInvalidSupplierManufacturerId(): void
    {
        $dto = new SupplierProductSearchDto();
        $dto->setSupplierManufacturerId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Supplier Manufacturer Id', $errors[0]->getMessage());
    }
}