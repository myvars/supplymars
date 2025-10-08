<?php

namespace App\Tests\Unit\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\SupplierStockChangeLog;
use App\Enum\DomainEventType;
use App\Event\SupplierProductStockWasChangedEvent;
use App\EventListener\DoctrineEvents\SupplierProductPublicIdResolver;
use App\EventListener\LogSupplierProductStockChange;
use App\ValueObject\CostChange;
use App\ValueObject\StockChange;
use App\ValueObject\SupplierProductPublicId;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LogSupplierProductStockChangeTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    public function testOnSupplierProductStockChangeLogsStockChange(): void
    {
        $supplierProductPublicId = SupplierProductPublicId::new();
        $stockChange = StockChange::from(0, 10);
        $costChange = CostChange::from('0.00', '5.00');

        $event = $this->createStub(SupplierProductStockWasChangedEvent::class);
        $event->method('publicId')->willReturn($supplierProductPublicId);
        $event->method('stockChange')->willReturn($stockChange);
        $event->method('costChange')->willReturn($costChange);
        $event->method('type')->willReturn(DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED);
        $event->method('occurredAt')->willReturn(new \DateTimeImmutable());

        $this->validator->method('validate')->willReturn($this->createStub(ConstraintViolationListInterface::class));

        $publicIdResolver = $this->createStub(SupplierProductPublicIdResolver::class);
        $publicIdResolver->method('resolve')->with($supplierProductPublicId)->willReturn(42);

        $logger = $this->createStub(LoggerInterface::class);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(SupplierStockChangeLog::class));
        $this->entityManager->expects($this->once())->method('flush');

        $listener = new LogSupplierProductStockChange(
            $this->entityManager,
            $this->validator,
            $publicIdResolver,
            $logger
        );

        $listener->__invoke($event);
    }
}
