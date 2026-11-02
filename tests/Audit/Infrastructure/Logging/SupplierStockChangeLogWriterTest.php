<?php

declare(strict_types=1);

namespace App\Tests\Audit\Infrastructure\Logging;

use App\Audit\Infrastructure\Logging\SupplierStockChangeLogWriter;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use App\Tests\Shared\Factory\SupplierStockChangeLogFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SupplierStockChangeLogWriterTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private SupplierStockChangeLogWriter $writer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->writer = self::getContainer()->get(SupplierStockChangeLogWriter::class);
    }

    public function testWritesValidLog(): void
    {
        $this->writer->write(
            type: DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            supplierProductId: 123,
            stockChange: StockChange::from(10, 15),
            costChange: CostChange::from('0.00', '1.00'),
            occurredAt: new \DateTimeImmutable(),
        );
        $this->em->clear();

        $rows = SupplierStockChangeLogFactory::repository()->findBy(['supplierProductId' => 123]);
        self::assertCount(1, $rows);
        self::assertSame(DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED, $rows[0]->getEventType());
        self::assertSame(15, $rows[0]->getStock());
        self::assertSame('1.00', $rows[0]->getCost());
    }

    public function testWritesWithCostChangedEventType(): void
    {
        $this->writer->write(
            type: DomainEventType::SUPPLIER_PRODUCT_COST_CHANGED,
            supplierProductId: 456,
            stockChange: StockChange::from(20, 20),
            costChange: CostChange::from('5.00', '8.00'),
            occurredAt: new \DateTimeImmutable(),
        );
        $this->em->clear();

        $rows = SupplierStockChangeLogFactory::repository()->findBy(['supplierProductId' => 456]);
        self::assertCount(1, $rows);
        self::assertSame(DomainEventType::SUPPLIER_PRODUCT_COST_CHANGED, $rows[0]->getEventType());
        self::assertSame('8.00', $rows[0]->getCost());
    }

    /**
     * @param numeric-string $cost
     */
    #[TestWith([-1, '1.00'], 'negative stock')]
    #[TestWith([10001, '1.00'], 'stock above 10000')]
    #[TestWith([10, '-5.00'], 'negative cost')]
    public function testRefusesInvalidLog(int $stock, string $cost): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->writer->write(
            type: DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            supplierProductId: 1,
            stockChange: StockChange::from(5, $stock),
            costChange: CostChange::from('1.00', $cost),
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
