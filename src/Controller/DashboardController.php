<?php

namespace App\Controller;

use App\DTO\OrderSummaryReportDto;
use App\DTO\ProductSalesReportDto;
use App\DTO\SearchDto\OverdueOrderSearchDto;
use App\Service\Dashboard\DashboardViewer;
use App\Service\Dashboard\ReportHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route(path: '/dashboard/', name: 'app_dashboard')]
    public function show(DashboardViewer $dashboardViewer): Response
    {
        return $this->render('dashboard/show.html.twig', [
            'report' => $dashboardViewer->build(),
        ]);
    }

    #[Route(path: '/dashboard/report/product/sales', name: 'app_dashboard_product_sales', methods: ['GET'])]
    public function productSales(
        ReportHandler $handler,
        #[MapQueryString] ProductSalesReportDto $dto = new ProductSalesReportDto(),
    ): Response {
        return $this->render('dashboard/product_sales.html.twig', [
            'report' => $handler->build('product-sales', $dto),
        ]);
    }

    #[Route(path: '/dashboard/report/order/summary', name: 'app_dashboard_order_summary', methods: ['GET'])]
    public function orderSummary(
        ReportHandler $handler,
        #[MapQueryString] OrderSummaryReportDto $dto = new OrderSummaryReportDto(),
    ): Response {
        return $this->render('dashboard/order_summary.html.twig', [
            'report' => $handler->build('order-summary', $dto),
        ]);
    }

    #[Route(path: '/dashboard/report/overdue/orders', name: 'app_dashboard_overdue_orders', methods: ['GET'])]
    public function overdueOrders(
        ReportHandler $handler,
        #[MapQueryString] OverdueOrderSearchDto $dto = new OverdueOrderSearchDto(),
    ): Response {
        return $this->render('dashboard/overdue_orders.html.twig', [
            'report' => $handler->build('overdue-orders', $dto),
        ]);
    }
}
