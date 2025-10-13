<?php

namespace App\Reporting\UI\Http\Controller;

use App\Reporting\Application\Report\OrderSummaryReportCriteria;
use App\Reporting\Application\Report\ProductSalesReportCriteria;
use App\Reporting\Application\Search\OverdueOrderSearchCriteria;
use App\Reporting\UI\Http\Dashboard\DashboardViewer;
use App\Reporting\UI\Http\Dashboard\ReportHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route(path: '/dashboard/', name: 'app_reporting_dashboard')]
    public function show(DashboardViewer $dashboardViewer): Response
    {
        return $this->render('reporting/show.html.twig', [
            'report' => $dashboardViewer->build(),
        ]);
    }

    #[Route(path: '/dashboard/report/product/sales', name: 'app_reporting_dashboard_product_sales', methods: ['GET'])]
    public function productSales(
        ReportHandler $handler,
        #[MapQueryString] ProductSalesReportCriteria $dto = new ProductSalesReportCriteria(),
    ): Response {
        return $this->render('reporting/product_sales.html.twig', [
            'report' => $handler->build('product-sales', $dto),
        ]);
    }

    #[Route(path: '/dashboard/report/order/summary', name: 'app_reporting_dashboard_order_summary', methods: ['GET'])]
    public function orderSummary(
        ReportHandler $handler,
        #[MapQueryString] OrderSummaryReportCriteria $dto = new OrderSummaryReportCriteria(),
    ): Response {
        return $this->render('reporting/order_summary.html.twig', [
            'report' => $handler->build('order-summary', $dto),
        ]);
    }

    #[Route(path: '/dashboard/report/overdue/orders', name: 'app_reporting_dashboard_overdue_orders', methods: ['GET'])]
    public function overdueOrders(
        ReportHandler $handler,
        #[MapQueryString] OverdueOrderSearchCriteria $dto = new OverdueOrderSearchCriteria(),
    ): Response {
        return $this->render('reporting/overdue_orders.html.twig', [
            'report' => $handler->build('overdue-orders', $dto),
        ]);
    }
}
