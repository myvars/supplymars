<?php

namespace App\Tests\Audit\Unit;

use App\Audit\Application\EventListener\SupplierStockChangeLogger;
use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStockWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\UI\Http\ArgumentResolver\SupplierProductPublicIdResolver;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LogSupplierProductStockWasChangedTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
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

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(SupplierStockChangeLog::class));
        $this->em->expects($this->once())->method('flush');

        $listener = new SupplierStockChangeLogger(
            $this->em,
            $this->validator,
            $publicIdResolver,
            $logger
        );

        $listener->__invoke($event);
    }
}
