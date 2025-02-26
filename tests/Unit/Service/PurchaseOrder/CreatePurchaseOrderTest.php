<?php

namespace App\Tests\Unit\Service\PurchaseOrder;

use App\Entity\CustomerOrder;
use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use App\Enum\ShippingMethod;
use App\Service\PurchaseOrder\CreatePurchaseOrder;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreatePurchaseOrderTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private DomainEventDispatcher $domainEventDispatcher;
    private CreatePurchaseOrder $createPurchaseOrder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->domainEventDispatcher = $this->createMock(DomainEventDispatcher::class);
        $this->createPurchaseOrder = new CreatePurchaseOrder($this->entityManager, $this->validator, $this->domainEventDispatcher);
    }

    public function testCreatePurchaseOrderSuccessfully(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $supplier = $this->createMock(Supplier::class);

        $customerOrder->method('getShippingMethod')->willReturn(ShippingMethod::NEXT_DAY);
        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        $this->domainEventDispatcher->expects($this->once())->method('dispatchProviderEvents');

        $purchaseOrder = $this->createPurchaseOrder->fromOrder($customerOrder, $supplier);
        $this->assertInstanceOf(PurchaseOrder::class, $purchaseOrder);
    }
}