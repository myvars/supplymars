<?php

declare(strict_types=1);

namespace App\Tests\Reporting\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

final class ReportsControllerTest extends WebTestCase
{
    use HasBrowser;

    public function testProductSalesRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/product/sales')
            ->assertSuccessful();
    }

    public function testOrderSummaryRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/order/summary')
            ->assertSuccessful();
    }

    public function testOverdueOrdersRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/overdue/orders')
            ->assertSuccessful();
    }

    public function testCustomerInsightsRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/customer/insights')
            ->assertSuccessful();
    }

    public function testCustomerInsightsRendersWithDurationFilter(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/customer/insights?duration=last_30')
            ->assertSuccessful();
    }

    public function testCustomerGeographicRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/customer/geographic')
            ->assertSuccessful();
    }

    public function testCustomerGeographicRendersWithDurationFilter(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/customer/geographic?duration=last_30')
            ->assertSuccessful();
    }

    public function testCustomerSegmentsRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/customer/segments')
            ->assertSuccessful();
    }

    public function testCustomerSegmentsRendersWithDurationFilter(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/customer/segments?duration=last_30')
            ->assertSuccessful();
    }

    #[WithStory(StaffUserStory::class)]
    public function testPoPerformanceListsRecentPurchaseOrderItems(): void
    {
        PurchaseOrderItemFactory::createOne([
            'product' => ProductFactory::createOne(['name' => 'Regolith Sieve']),
            'supplier' => SupplierFactory::createOne(['name' => 'Olympus Supply']),
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/reports/po/performance')
            ->assertSuccessful()
            ->assertSee('Regolith Sieve')
            ->assertSee('Olympus Supply');
    }
}
