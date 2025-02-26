<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\SupplierProduct;
use App\Entity\SupplierStockChangeLog;
use App\Enum\DomainEventType;
use App\Event\SupplierProductCostChangedEvent;
use App\Event\SupplierProductStockChangedEvent;
use App\EventListener\LogSupplierProductStockChange;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LogSupplierProductStockChangeTest extends TestCase
{

    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    public function testOnSupplierProductStockChangeLogsStockChange(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getId')->willReturn(1);
        $supplierProduct->method('getStock')->willReturn(10);
        $supplierProduct->method('getCost')->willReturn('100.00');

        $event = $this->createMock(SupplierProductStockChangedEvent::class);
        $event->method('getDomainEventType')->willReturn(DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED);
        $event->method('getSupplierProduct')->willReturn($supplierProduct);
        $event->method('getEventTimestamp')->willReturn(new \DateTimeImmutable());

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(SupplierStockChangeLog::class));
        $this->entityManager->expects($this->once())->method('flush');

        $listener = new LogSupplierProductStockChange($this->entityManager, $this->validator);
        $listener->onSupplierProductStockChange($event);
    }

    public function testOnSupplierProductCostChangeLogsCostChange(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getId')->willReturn(1);
        $supplierProduct->method('getStock')->willReturn(10);
        $supplierProduct->method('getCost')->willReturn('50.00');

        $event = $this->createMock(SupplierProductCostChangedEvent::class);
        $event->method('getDomainEventType')->willReturn(DomainEventType::SUPPLIER_PRODUCT_COST_CHANGED);
        $event->method('getSupplierProduct')->willReturn($supplierProduct);
        $event->method('getEventTimestamp')->willReturn(new \DateTimeImmutable());

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(SupplierStockChangeLog::class));
        $this->entityManager->expects($this->once())->method('flush');

        $listener = new LogSupplierProductStockChange($this->entityManager, $this->validator);
        $listener->onSupplierProductCostChange($event);
    }
}