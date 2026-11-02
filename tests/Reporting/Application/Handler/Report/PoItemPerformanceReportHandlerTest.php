<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Reporting\Application\Handler\Report\PoItemPerformanceReportHandler;
use App\Reporting\Application\Report\PoItemPerformanceReportCriteria;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PoItemPerformanceReportHandlerTest extends KernelTestCase
{
    private PoItemPerformanceReportHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(PoItemPerformanceReportHandler::class);
    }

    public function testReturnsSuccessWithExpectedPayloadStructure(): void
    {
        $criteria = new PoItemPerformanceReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertSame('Report created', $result->message);
        self::assertArrayHasKey('summary', $result->payload);
        self::assertArrayHasKey('items', $result->payload);
    }

    public function testReturnsEmptyResultsWhenNoData(): void
    {
        $criteria = new PoItemPerformanceReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertIsArray($result->payload['summary']);
    }

    public function testHandlesOutOfRangePageGracefully(): void
    {
        $criteria = new PoItemPerformanceReportCriteria();
        $criteria->setPage(999);

        $result = ($this->handler)($criteria);

        // Should reset to page 1 and return success
        self::assertTrue($result->ok);
        self::assertArrayHasKey('items', $result->payload);
    }

    public function testReturnsItemsWhenDataExists(): void
    {
        // Create some PO items for the report
        PurchaseOrderItemFactory::createOne();

        $criteria = new PoItemPerformanceReportCriteria();
        $criteria->setDuration(SalesDuration::LAST_30->value);

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertArrayHasKey('items', $result->payload);
    }

    #[TestWith(['DESC'])]
    #[TestWith(['ASC'])]
    public function testSortsItemsByProfit(string $direction): void
    {
        $items = [
            PurchaseOrderItemFactory::createOne(['quantity' => 1]),
            PurchaseOrderItemFactory::createOne(['quantity' => 3]),
        ];
        $profit = static fn (PurchaseOrderItem $item): string => bcsub(
            bcmul((string) $item->getQuantity(), $item->getCustomerOrderItem()->getPrice(), 2),
            $item->getTotalPrice(),
            2,
        );
        usort($items, static fn (PurchaseOrderItem $a, PurchaseOrderItem $b): int => $direction === 'DESC'
            ? bccomp($profit($b), $profit($a), 2)
            : bccomp($profit($a), $profit($b), 2));
        self::assertNotSame($profit($items[0]), $profit($items[1]));

        $criteria = new PoItemPerformanceReportCriteria();
        $criteria->setSort('profit');
        $criteria->setSortDirection($direction);

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        $listed = [...$result->payload['items']->getCurrentPageResults()];
        self::assertSame(
            array_map(static fn (PurchaseOrderItem $item): ?int => $item->getId(), $items),
            array_map(static fn (PurchaseOrderItem $item): ?int => $item->getId(), $listed),
        );
    }
}
