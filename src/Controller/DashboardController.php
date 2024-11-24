<?php

namespace App\Controller;

use App\DTO\OrderSalesDashboardDto;
use App\DTO\ProductSalesDashboardDto;
use App\Service\Sales\OrderDashboardManager;
use App\Service\Sales\ProductSalesDashboardManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/products', name: 'app_product_sales_dashboard', methods: ['GET'])]
    public function products(
        ProductSalesDashboardManager $dashboardManager,
        #[MapQueryString] ProductSalesDashboardDto $dto = new ProductSalesDashboardDto()
    ): Response {
        $dashboardManager->createFromDto($dto);

        return $this->render('dashboard/product_sales_dashboard.html.twig', [
            'sales' => $dashboardManager->getSales(),
            'summary' => $dashboardManager->getSummary(),
            'productSalesChart' => $dashboardManager->getProductSalesChart(),
        ]);
    }

    #[Route('/orders', name: 'app_orders_dashboard', methods: ['GET'])]
    public function orders(
        OrderDashboardManager $dashboardManager,
        #[MapQueryString] OrderSalesDashboardDto $dto = new OrderSalesDashboardDto()
    ): Response {
        $dashboardManager->createFromDto($dto);

        return $this->render('dashboard/order_dashboard.html.twig', [
            'summary' => $dashboardManager->getSummary(),
            'orderSalesChart' => $dashboardManager->getOrderSalesChart(),
            'orderProgressChart' => $dashboardManager->getOrderProgressChart(),
        ]);
    }
}