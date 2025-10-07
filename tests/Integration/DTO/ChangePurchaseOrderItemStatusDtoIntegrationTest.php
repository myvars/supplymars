<?php

namespace App\Tests\Integration\DTO;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Enum\PurchaseOrderStatus;
use App\Factory\PurchaseOrderItemFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ChangePurchaseOrderItemStatusDtoIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidChangePurchaseOrderItemStatusDto(): void
    {
        $item = PurchaseOrderItemFactory::createOne();

        $dto = new ChangePurchaseOrderItemStatusDto(
            $item->getId(),
            PurchaseOrderStatus::PROCESSING
        );

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testChangePurchaseOrderItemStatusDtoWithSameStatus(): void
    {
        $item = PurchaseOrderItemFactory::createOne()->_real();

        $dto = new ChangePurchaseOrderItemStatusDto(
            $item->getId(),
            $item->getStatus()
        );

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }

    public function testInvalidPurchaseOrderItemId(): void
    {

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PurchaseOrderItem not found');

        $dto = new ChangePurchaseOrderItemStatusDto(
            -1,
            PurchaseOrderStatus::PROCESSING
        );
        $this->validator->validate($dto);
    }

    public function testInvalidPurchaseOrderItemStatusChange(): void
    {
        $item = PurchaseOrderItemFactory::createOne();

        $dto = new ChangePurchaseOrderItemStatusDto(
            $item->getId(),
            PurchaseOrderStatus::ACCEPTED
        );

        $violations = $this->validator->validate($dto);
        $this->assertSame('Status can not be set to ACCEPTED.', $violations[0]->getMessage());
    }
}
