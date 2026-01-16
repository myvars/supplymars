<?php

namespace App\Tests\Integration\Service\Dashboard;

use App\Reporting\UI\Http\Dashboard\DashboardViewer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DashboardViewerIntegrationTest extends KernelTestCase
{
    private DashboardViewer $dashboardViewer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->dashboardViewer = static::getContainer()->get(DashboardViewer::class);
    }

    public function testBuild(): void
    {
        $result = $this->dashboardViewer->build();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('orderSalesSummary', $result);
        $this->assertArrayHasKey('orderSalesCompareSummary', $result);
        $this->assertArrayHasKey('productSalesSummary', $result);
        $this->assertArrayHasKey('productSalesCompareSummary', $result);
        $this->assertArrayHasKey('overdueOrderSummary', $result);
        $this->assertArrayHasKey('rejectedPoSummary', $result);
        $this->assertArrayHasKey('latestProductSales', $result);
        $this->assertArrayHasKey('latestOrders', $result);
    }
}
