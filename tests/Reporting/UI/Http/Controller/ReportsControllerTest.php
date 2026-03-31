<?php

namespace App\Tests\Reporting\UI\Http\Controller;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class ReportsControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

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

    public function testCustomerInsightsRequiresAuthentication(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get('/reports/customer/insights')
            ->assertRedirectedTo('/login');
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

    public function testCustomerGeographicRequiresAuthentication(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get('/reports/customer/geographic')
            ->assertRedirectedTo('/login');
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

    public function testCustomerSegmentsRequiresAuthentication(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get('/reports/customer/segments')
            ->assertRedirectedTo('/login');
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
}
