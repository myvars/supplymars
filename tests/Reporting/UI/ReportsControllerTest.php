<?php

declare(strict_types=1);

namespace App\Tests\Reporting\UI;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use App\Tests\Shared\Factory\CustomerGeographicSummaryFactory;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerSalesSummaryFactory;
use App\Tests\Shared\Factory\CustomerSegmentSummaryFactory;
use App\Tests\Shared\Factory\OrderSalesSummaryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductSalesFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

use function Zenstruck\Foundry\force;

final class ReportsControllerTest extends WebTestCase
{
    use HasBrowser;

    /**
     * @param \Closure(): void $seed
     */
    #[DataProvider('reportProvider')]
    public function testReportShowsWhatItReadFromTheDatabase(string $url, \Closure $seed, string $expected): void
    {
        $seed();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($url)
            ->assertSuccessful()
            ->assertSee($expected);
    }

    /**
     * @return iterable<string, array{string, \Closure(): void, string}>
     */
    public static function reportProvider(): iterable
    {
        $today = new \DateTime()->format('Y-m-d');

        yield 'product sales' => ['/reports/product/sales', static function () use ($today): void {
            ProductSalesFactory::createOne([
                'product' => ProductFactory::createOne(['name' => 'Regolith']),
                'dateString' => $today,
            ]);
        }, 'Regolith'];

        yield 'order summary' => ['/reports/order/summary', static function (): void {
            OrderSalesSummaryFactory::createOne([
                'orderSalesType' => OrderSalesType::create(SalesDuration::LAST_30),
                'orderCount' => 4321,
            ]);
        }, '4,321'];

        yield 'overdue orders' => ['/reports/overdue/orders', static function (): void {
            CustomerOrderFactory::createOne([
                'customer' => UserFactory::createOne(['fullName' => 'Ada Overdue']),
                'dueDate' => force(new \DateTimeImmutable('-2 days')),
            ]);
        }, 'Ada Overdue'];

        yield 'customer insights' => ['/reports/customer/insights', static function (): void {
            CustomerSalesSummaryFactory::createOne([
                'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
                'totalCustomers' => 4321,
            ]);
        }, '4,321'];

        // The duration filter reaches the report: last 7 days' summary, not the default last 30
        yield 'customer geographic, last 7 days' => ['/reports/customer/geographic?duration=last7', static function (): void {
            CustomerGeographicSummaryFactory::createOne([
                'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_7),
                'city' => 'Olympus Mons',
            ]);
        }, 'Olympus Mons'];

        yield 'customer segments' => ['/reports/customer/segments', static function (): void {
            CustomerSegmentSummaryFactory::createOne([
                'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
                'customerCount' => 4321,
            ]);
        }, '4,321'];

        yield 'PO performance' => ['/reports/po/performance', static function (): void {
            PurchaseOrderItemFactory::createOne([
                'supplier' => SupplierFactory::createOne(['name' => 'Olympus Supply']),
            ]);
        }, 'Olympus Supply'];
    }
}
