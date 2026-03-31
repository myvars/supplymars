<?php

namespace App\Tests\Home\UI;

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

    public function testDashboardRequiresAuthentication(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get('/dashboard/')
            ->assertRedirectedTo('/login');
    }

    public function testDashboardRendersForAuthenticatedStaff(): void
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

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/dashboard/')
            ->assertSuccessful()
            ->assertSee('Latest Orders')
            ->assertSee('Top Products Today');
    }
}
