<?php

namespace App\Reporting\UI\Http\Controller;

use App\Reporting\Application\Handler\Report\CustomerGeographicReportHandler;
use App\Reporting\Application\Handler\Report\CustomerInsightsReportHandler;
use App\Reporting\Application\Handler\Report\CustomerSegmentReportHandler;
use App\Reporting\Application\Handler\Report\OrderSummaryReportHandler;
use App\Reporting\Application\Handler\Report\OverdueOrdersReportHandler;
use App\Reporting\Application\Handler\Report\PoItemPerformanceReportHandler;
use App\Reporting\Application\Handler\Report\ProductSalesReportHandler;
use App\Reporting\Application\Report\CustomerGeographicReportCriteria;
use App\Reporting\Application\Report\CustomerInsightsReportCriteria;
use App\Reporting\Application\Report\CustomerSegmentReportCriteria;
use App\Reporting\Application\Report\OrderSummaryReportCriteria;
use App\Reporting\Application\Report\OverdueOrderReportCriteria;
use App\Reporting\Application\Report\PoItemPerformanceReportCriteria;
use App\Reporting\Application\Report\ProductSalesReportCriteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ReportsController extends AbstractController
{
    #[Route(path: '/reports/product/sales', name: 'app_reports_product_sales', methods: ['GET'])]
    public function productSales(
        ProductSalesReportHandler $handler,
        #[MapQueryString] ProductSalesReportCriteria $criteria = new ProductSalesReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/product_sales.html.twig', [
            'report' => $result->payload,
        ]);
    }

    #[Route(path: '/reports/order/summary', name: 'app_reports_order_summary', methods: ['GET'])]
    public function orderSummary(
        OrderSummaryReportHandler $handler,
        #[MapQueryString] OrderSummaryReportCriteria $criteria = new OrderSummaryReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/order_summary.html.twig', [
            'report' => $result->payload,
        ]);
    }

    #[Route(path: '/reports/overdue/orders', name: 'app_reports_overdue_orders', methods: ['GET'])]
    public function overdueOrders(
        OverdueOrdersReportHandler $handler,
        #[MapQueryString] OverdueOrderReportCriteria $criteria = new OverdueOrderReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/overdue_orders.html.twig', [
            'report' => $result->payload,
        ]);
    }

    #[Route(path: '/reports/customer/insights', name: 'app_reports_customer_insights', methods: ['GET'])]
    public function customerInsights(
        CustomerInsightsReportHandler $handler,
        #[MapQueryString] CustomerInsightsReportCriteria $criteria = new CustomerInsightsReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/customer_insights.html.twig', [
            'report' => $result->payload,
        ]);
    }

    #[Route(path: '/reports/customer/geographic', name: 'app_reports_customer_geographic', methods: ['GET'])]
    public function customerGeographic(
        CustomerGeographicReportHandler $handler,
        #[MapQueryString] CustomerGeographicReportCriteria $criteria = new CustomerGeographicReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/customer_geographic.html.twig', [
            'report' => $result->payload,
        ]);
    }

    #[Route(path: '/reports/customer/segments', name: 'app_reports_customer_segments', methods: ['GET'])]
    public function customerSegments(
        CustomerSegmentReportHandler $handler,
        #[MapQueryString] CustomerSegmentReportCriteria $criteria = new CustomerSegmentReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/customer_segments.html.twig', [
            'report' => $result->payload,
        ]);
    }

    #[Route(path: '/reports/po/performance', name: 'app_reports_po_performance', methods: ['GET'])]
    public function poPerformance(
        PoItemPerformanceReportHandler $handler,
        #[MapQueryString] PoItemPerformanceReportCriteria $criteria = new PoItemPerformanceReportCriteria(),
    ): Response {
        $result = $handler($criteria);

        return $this->render('reporting/po_item_performance.html.twig', [
            'report' => $result->payload,
        ]);
    }
}
