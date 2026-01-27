<?php

namespace App\Reporting\UI\Http\Controller;

use App\Reporting\Application\Handler\Report\DashboardReportHandler;
use App\Reporting\Application\Handler\Report\OrderSummaryReportHandler;
use App\Reporting\Application\Handler\Report\OverdueOrdersReportHandler;
use App\Reporting\Application\Handler\Report\ProductSalesReportHandler;
use App\Reporting\Application\Report\OrderSummaryReportCriteria;
use App\Reporting\Application\Report\OverdueOrderReportCriteria;
use App\Reporting\Application\Report\ProductSalesReportCriteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route(path: '/dashboard/', name: 'app_reporting_dashboard')]
    public function show(DashboardReportHandler $handler): Response
    {
        $result = $handler();

        return $this->render('reporting/show.html.twig', [
            'report' => $result->payload,
        ]);
    }

    #[Route(path: '/dashboard/report/product/sales', name: 'app_reporting_dashboard_product_sales', methods: ['GET'])]
    public function productSales(
        ProductSalesReportHandler $handler,
        #[MapQueryString] ProductSalesReportCriteria $criteria = new ProductSalesReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/product_sales.html.twig', [
            'report' => $result->payload,
        ]);
    }

    #[Route(path: '/dashboard/report/order/summary', name: 'app_reporting_dashboard_order_summary', methods: ['GET'])]
    public function orderSummary(
        OrderSummaryReportHandler $handler,
        #[MapQueryString] OrderSummaryReportCriteria $criteria = new OrderSummaryReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/order_summary.html.twig', [
            'report' => $result->payload,
        ]);
    }

    #[Route(path: '/dashboard/report/overdue/orders', name: 'app_reporting_dashboard_overdue_orders', methods: ['GET'])]
    public function overdueOrders(
        OverdueOrdersReportHandler $handler,
        #[MapQueryString] OverdueOrderReportCriteria $criteria = new OverdueOrderReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/overdue_orders.html.twig', [
            'report' => $result->payload,
        ]);
    }
}
