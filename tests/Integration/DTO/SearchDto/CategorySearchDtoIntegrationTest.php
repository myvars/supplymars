<?php

namespace App\Tests\Integration\DTO\SearchDto;

use App\DTO\SearchDto\CategorySearchDto;
use App\Enum\PriceModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategorySearchDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCategorySearchDto(): void
    {
        $dto = new CategorySearchDto();
        $dto->setSort('name');
        $dto->setSortDirection('ASC');
        $dto->setPriceModel(PriceModel::PRETTY_99->value);
        $dto->setManagerId(123);
        $dto->setVatRateId(456);

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidManagerId(): void
    {
        $dto = new CategorySearchDto();
        $dto->setManagerId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Manager Id', $errors[0]->getMessage());
    }

    public function testInvalidVatRateId(): void
    {
        $dto = new CategorySearchDto();
        $dto->setVatRateId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Vat Rate Id', $errors[0]->getMessage());
    }
}