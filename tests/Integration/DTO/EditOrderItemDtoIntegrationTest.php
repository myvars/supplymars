<?php

namespace App\Tests\Integration\DTO;

use App\DTO\EditOrderItemDto;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\PurchaseOrderItemFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EditOrderItemDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidEditOrderItemDto(): void
    {
        $item = CustomerOrderItemFactory::createOne();

        $dto = new EditOrderItemDto(
            $item->getId(),
            10,
            '100.00',
            true
        );

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidOrderItemId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CustomerOrderItem not found');

        $dto = new EditOrderItemDto(
            0,
            10,
            '100.00',
            true
        );
        $violations = $this->validator->validate($dto);
    }

    public function testQuantityIsRequired(): void
    {
        $item = CustomerOrderItemFactory::createOne();

        $dto = new EditOrderItemDto(
            $item->getId(),
            null,
            '100.00',
            true
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a product quantity', $violations[0]->getMessage());
    }

    public function testInvalidQuantity(): void
    {
        $item = CustomerOrderItemFactory::createOne();

        $dto = new EditOrderItemDto(
            $item->getId(),
            -1,
            '100.00',
            true
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a product quantity (0 to 100000)', $violations[0]->getMessage());
    }

    public function testPriceIncVatIsRequired(): void
    {
        $item = CustomerOrderItemFactory::createOne();

        $dto = new EditOrderItemDto(
            $item->getId(),
            10,
            null,
            true
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a product price inc VAT', $violations[0]->getMessage());
    }

    public function testInvalidPriceIncVat(): void
    {
        $item = CustomerOrderItemFactory::createOne();

        $dto = new EditOrderItemDto(
            $item->getId(),
            10,
            '1000000',
            true
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a product price inc VAT (0 to 100000)', $violations[0]->getMessage());
    }

    public function testInvalidMinQuantity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $dto = new EditOrderItemDto(
            $purchaseOrderItem->getCustomerOrderItem()->getId(),
            0, // 1 already attached to the purchase order item
            '100',
            true
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('minimum quantity is 1', $violations[0]->getMessage());
    }
}