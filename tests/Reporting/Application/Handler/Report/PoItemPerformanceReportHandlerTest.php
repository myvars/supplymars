<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Reporting\Application\Handler\Report\PoItemPerformanceReportHandler;
use App\Reporting\Application\Report\PoItemPerformanceReportCriteria;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
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

    /**
     * @param list<int> $expectedQuantities
     */
    #[TestWith(['DESC', [3, 2, 1]])]
    #[TestWith(['ASC', [1, 2, 3]])]
    public function testSortsItemsByProfit(string $direction, array $expectedQuantities): void
    {
        // Sells at 12.50 from a 10.00 source (markup 25%), so each item's profit is 2.50 × its quantity
        $category = CategoryFactory::createOne(['defaultMarkup' => '25.000', 'priceModel' => PriceModel::DEFAULT]);
        $product = ProductFactory::createOne(['category' => $category]);
        $source = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 100]);
        // Created out of order, so neither direction matches the order the rows were written in
        foreach ([2, 1, 3] as $quantity) {
            PurchaseOrderItemFactory::createOne([
                'product' => $product,
                'supplier' => $source->getSupplier(),
                'supplierProduct' => $source,
                'quantity' => $quantity,
            ]);
        }

        $criteria = new PoItemPerformanceReportCriteria();
        $criteria->setSort('profit');
        $criteria->setSortDirection($direction);

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        $listed = [...$result->payload['items']->getCurrentPageResults()];
        self::assertSame(
            $expectedQuantities,
            array_map(static fn (PurchaseOrderItem $item): int => $item->getQuantity(), $listed),
        );
    }
}
