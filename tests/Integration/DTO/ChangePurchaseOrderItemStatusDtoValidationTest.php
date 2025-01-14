<?php

namespace App\Tests\Integration\DTO;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Enum\PurchaseOrderStatus;
use AssertionError;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ChangePurchaseOrderItemStatusDtoValidationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testPurchaseOrderWithInvalidItemId(): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('assert($purchaseOrderItem instanceof PurchaseOrderItem)');

        $dto = new ChangePurchaseOrderItemStatusDto(-1, PurchaseOrderStatus::ACCEPTED);
        $this->validator->validate($dto);
    }

    // test for invalid status change
/*    public function testPurchaseOrderItemStatusChangeIsInvalid(): void
    {
        $dto = new ChangePurchaseOrderItemStatusDto($purchaseOrderItem->getId(), PurchaseOrderStatus::SHIPPED);
        $violations = $this->validator->validate($dto);

        $this->assertCount(1, $violations);
        $this->assertSame('Status can not be set to SHIPPED', $violations->get(0)->getMessage());
    }*/


}
