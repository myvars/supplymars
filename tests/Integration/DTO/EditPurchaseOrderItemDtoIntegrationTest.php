<?php

namespace App\Tests\Integration\DTO;

use App\DTO\EditPurchaseOrderItemDto;
use App\Factory\PurchaseOrderItemFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class EditPurchaseOrderItemDtoIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidEditPurchaseOrderItemDto(): void
    {
        $item = PurchaseOrderItemFactory::createOne();

        $dto = new EditPurchaseOrderItemDto(
            $item->getId(),
            1
        );

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidPurchaseOrderItemId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The MaxPurchaseOrderItemQty constraint can only be used with a valid purchaseOrderItemId'
        );

        $dto = new EditPurchaseOrderItemDto(
            0,
            1
        );
        $this->validator->validate($dto);
    }

    public function testQuantityIsRequired(): void
    {
        $item = PurchaseOrderItemFactory::createOne();

        $dto = new EditPurchaseOrderItemDto(
            $item->getId(),
            null
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a product quantity', $violations[0]->getMessage());
    }

    public function testInvalidQuantity(): void
    {
        $item = PurchaseOrderItemFactory::createOne();

        $dto = new EditPurchaseOrderItemDto(
            $item->getId(),
            -1
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Please enter a product quantity (0 to 100000)', $violations[0]->getMessage());
    }

    public function testInvalidMaxQuantity(): void
    {
        $item = PurchaseOrderItemFactory::createOne();

        $dto = new EditPurchaseOrderItemDto(
            $item->getId(),
            2 // max PO Item quantity is 1
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('maximum quantity is 1', $violations[0]->getMessage());
    }
}
