<?php

namespace App\Tests\Integration\DTO\SearchDto;

use App\DTO\SearchDto\OrderSearchDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderSearchDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidOrderSearchDto(): void
    {
        $dto = new OrderSearchDto();
        $dto->setSort('createdAt');
        $dto->setSortDirection('ASC');
        $dto->setCustomerOrderId(123);
        $dto->setPurchaseOrderId(456);
        $dto->setCustomerId(789);
        $dto->setProductId(101);
        $dto->setStartDate('2023-01-01');
        $dto->setEndDate('2023-12-31');
        $dto->setOrderStatus('completed');

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidCustomerOrderId(): void
    {
        $dto = new OrderSearchDto();
        $dto->setCustomerOrderId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Customer Order Id', $errors[0]->getMessage());
    }

    public function testInvalidPurchaseOrderId(): void
    {
        $dto = new OrderSearchDto();
        $dto->setPurchaseOrderId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Purchase Order Id', $errors[0]->getMessage());
    }

    public function testInvalidCustomerId(): void
    {
        $dto = new OrderSearchDto();
        $dto->setCustomerId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Customer Id', $errors[0]->getMessage());
    }

    public function testInvalidProductId(): void
    {
        $dto = new OrderSearchDto();
        $dto->setProductId(0);

        $errors = $this->validator->validate($dto);
        $this->assertSame('Please enter a valid Product Id', $errors[0]->getMessage());
    }

    public function testInvalidStartDate(): void
    {
        $dto = new OrderSearchDto();
        $dto->setStartDate('invalid-date');

        $errors = $this->validator->validate($dto);
        $this->assertSame('This value is not a valid date.', $errors[0]->getMessage());
    }

    public function testInvalidEndDate(): void
    {
        $dto = new OrderSearchDto();
        $dto->setEndDate('invalid-date');

        $errors = $this->validator->validate($dto);
        $this->assertSame('This value is not a valid date.', $errors[0]->getMessage());
    }
}