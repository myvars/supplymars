<?php

namespace App\Tests\Unit\Service\PurchaseOrder;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\CustomerOrder;
use App\Entity\Supplier;
use App\Enum\ShippingMethod;
use App\Service\PurchaseOrder\CreatePurchaseOrder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreatePurchaseOrderTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $validator;

    private CreatePurchaseOrder $createPurchaseOrder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->createPurchaseOrder = new CreatePurchaseOrder($this->entityManager, $this->validator);
    }

    public function testCreatePurchaseOrderSuccessfully(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $supplier = $this->createMock(Supplier::class);

        $customerOrder->method('getShippingMethod')->willReturn(ShippingMethod::NEXT_DAY);
        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->createPurchaseOrder->fromOrder($customerOrder, $supplier);
    }
}
