<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\SupplierStockChangeLog;
use App\Enum\DomainEventType;
use App\Event\SupplierProductStockWasChangedEvent;
use App\EventListener\LogSupplierProductStockChange;
use App\ValueObject\StockChange;
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
        $event = $this->createMock(SupplierProductStockWasChangedEvent::class);
        $event->method('type')->willReturn(DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED);
        $event->method('publicId')->willReturn(1);
        $event->method('stockChange')->willReturn(StockChange::class);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(SupplierStockChangeLog::class));
        $this->entityManager->expects($this->once())->method('flush');

        $listener = new LogSupplierProductStockChange($this->entityManager, $this->validator);
        $listener->__invoke($event);
    }
}
