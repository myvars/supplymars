<?php

namespace App\Tests\Reporting\UI\Http\Controller;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Tests\Shared\Factory\OrderSalesSummaryFactory;
use App\Tests\Shared\Factory\ProductSalesSummaryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class DashboardControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    private function createRequiredSummaryData(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $weekAgo = new \DateTime('-7 days')->format('Y-m-d');

        OrderSalesSummaryFactory::createOne([
            'orderSalesType' => OrderSalesType::create(SalesDuration::TODAY),
            'dateString' => $today,
            'orderCount' => 1,
            'orderValue' => 100.00,
        ]);

        OrderSalesSummaryFactory::createOne([
            'orderSalesType' => OrderSalesType::create(SalesDuration::WEEK_AGO),
            'dateString' => $weekAgo,
            'orderCount' => 1,
            'orderValue' => 100.00,
        ]);

        ProductSalesSummaryFactory::createOne([
            'productSalesType' => ProductSalesType::create(SalesType::ALL, SalesDuration::TODAY),
            'salesId' => 1,
            'dateString' => $today,
            'salesQty' => 1,
            'salesCost' => 50.00,
            'salesValue' => 100.00,
        ]);

        ProductSalesSummaryFactory::createOne([
            'productSalesType' => ProductSalesType::create(SalesType::ALL, SalesDuration::WEEK_AGO),
            'salesId' => 1,
            'dateString' => $weekAgo,
            'salesQty' => 1,
            'salesCost' => 50.00,
            'salesValue' => 100.00,
        ]);
    }

    public function testShowRequiresAuthentication(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get('/dashboard/')
            ->assertRedirectedTo('/login');
    }

    public function testShowRendersForAuthenticatedStaff(): void
    {
        $this->createRequiredSummaryData();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/')
            ->assertSuccessful()
            ->assertSee("Today's Latest Orders")
            ->assertSee("Today's Top Products")
            ->assertSeeElement('table');
    }

    public function testProductSalesRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/report/product/sales')
            ->assertSuccessful();
    }

    public function testOrderSummaryRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/report/order/summary')
            ->assertSuccessful();
    }

    public function testOverdueOrdersRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/report/overdue/orders')
            ->assertSuccessful();
    }

    public function testCustomerInsightsRequiresAuthentication(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get('/dashboard/report/customer/insights')
            ->assertRedirectedTo('/login');
    }

    public function testCustomerInsightsRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/report/customer/insights')
            ->assertSuccessful();
    }

    public function testCustomerInsightsRendersWithDurationFilter(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/report/customer/insights?duration=last_30')
            ->assertSuccessful();
    }

    public function testCustomerGeographicRequiresAuthentication(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get('/dashboard/report/customer/geographic')
            ->assertRedirectedTo('/login');
    }

    public function testCustomerGeographicRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/report/customer/geographic')
            ->assertSuccessful();
    }

    public function testCustomerGeographicRendersWithDurationFilter(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/report/customer/geographic?duration=last_30')
            ->assertSuccessful();
    }

    public function testCustomerSegmentsRequiresAuthentication(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get('/dashboard/report/customer/segments')
            ->assertRedirectedTo('/login');
    }

    public function testCustomerSegmentsRendersWithDefaultCriteria(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/report/customer/segments')
            ->assertSuccessful();
    }

    public function testCustomerSegmentsRendersWithDurationFilter(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/report/customer/segments?duration=last_30')
            ->assertSuccessful();
    }
}
