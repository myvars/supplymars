<?php

namespace App\Tests\Unit\Service\PurchaseOrder;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Service\PurchaseOrder\CreatePurchaseOrder;
use App\Shared\Domain\ValueObject\ShippingMethod;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreatePurchaseOrderTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private CreatePurchaseOrder $createPurchaseOrder;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->createPurchaseOrder = new CreatePurchaseOrder($this->em, $this->validator);
    }

    public function testCreatePurchaseOrderSuccessfully(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $supplier = $this->createMock(Supplier::class);

        $customerOrder->method('getShippingMethod')->willReturn(ShippingMethod::NEXT_DAY);
        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->createPurchaseOrder->fromOrder($customerOrder, $supplier);
    }
}
