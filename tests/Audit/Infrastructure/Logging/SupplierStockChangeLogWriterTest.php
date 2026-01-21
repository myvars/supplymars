<?php

namespace App\Tests\Audit\Infrastructure\Logging;

use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use App\Audit\Domain\Repository\SupplierStockChangeLogRepository;
use App\Audit\Infrastructure\Logging\SupplierStockChangeLogWriter;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SupplierStockChangeLogWriterTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private SupplierStockChangeLogRepository $repo;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repo = self::getContainer()->get(SupplierStockChangeLogRepository::class);
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testWritesValidLogAndFlushes(): void
    {
        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $writer = new SupplierStockChangeLogWriter($this->repo, $this->validator, $flusher);

        $writer->write(
            type: DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            supplierProductId: 123,
            stockChange: StockChange::from(10, 15),
            costChange: CostChange::from('0.00', '1.00'),
            occurredAt: new \DateTimeImmutable(),
        );

        $this->em->flush();
        $this->em->clear();

        $rows = $this->em->getRepository(SupplierStockChangeLog::class)->findBy(['supplierProductId' => 123]);
        self::assertCount(1, $rows);
        self::assertSame(15, $rows[0]->getStock());
        self::assertSame('1.00', $rows[0]->getCost());
    }

    public function testThrowsOnInvalidLog(): void
    {
        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $writer = new SupplierStockChangeLogWriter($this->repo, $this->validator, $flusher);

        $this->expectException(\InvalidArgumentException::class);

        $writer->write(
            type: DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            supplierProductId: 1,
            stockChange: StockChange::from(5, -1), // invalid per Range(min:0)
            costChange: CostChange::from('1.00', '1.00'),
            occurredAt: new \DateTimeImmutable(),
        );
    }

    public function testThrowsOnNegativeCost(): void
    {
        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $writer = new SupplierStockChangeLogWriter($this->repo, $this->validator, $flusher);

        $this->expectException(\InvalidArgumentException::class);

        $writer->write(
            type: DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            supplierProductId: 1,
            stockChange: StockChange::from(5, 10),
            costChange: CostChange::from('1.00', '-5.00'), // invalid per PositiveOrZero
            occurredAt: new \DateTimeImmutable(),
        );
    }

    public function testThrowsOnStockExceedingMax(): void
    {
        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $writer = new SupplierStockChangeLogWriter($this->repo, $this->validator, $flusher);

        $this->expectException(\InvalidArgumentException::class);

        $writer->write(
            type: DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            supplierProductId: 1,
            stockChange: StockChange::from(100, 10001), // invalid per Range(max:10000)
            costChange: CostChange::from('1.00', '1.00'),
            occurredAt: new \DateTimeImmutable(),
        );
    }

    public function testWritesWithCostChangedEventType(): void
    {
        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $writer = new SupplierStockChangeLogWriter($this->repo, $this->validator, $flusher);

        $writer->write(
            type: DomainEventType::SUPPLIER_PRODUCT_COST_CHANGED,
            supplierProductId: 456,
            stockChange: StockChange::from(20, 20),
            costChange: CostChange::from('5.00', '8.00'),
            occurredAt: new \DateTimeImmutable(),
        );

        $this->em->flush();
        $this->em->clear();

        $rows = $this->em->getRepository(SupplierStockChangeLog::class)->findBy(['supplierProductId' => 456]);
        self::assertCount(1, $rows);
        self::assertSame(DomainEventType::SUPPLIER_PRODUCT_COST_CHANGED, $rows[0]->getEventType());
        self::assertSame('8.00', $rows[0]->getCost());
    }
}
