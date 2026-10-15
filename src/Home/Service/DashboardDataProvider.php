<?php

declare(strict_types=1);

namespace App\Home\Service;

use App\Catalog\Domain\Repository\CategoryRepository;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Domain\Repository\OrderRepository;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Domain\Repository\OrderSalesSummaryRepository;
use App\Reporting\Domain\Repository\ProductSalesRepository;
use App\Reporting\Domain\Repository\ProductSalesSummaryRepository;

final readonly class DashboardDataProvider
{
    public function __construct(
        private OrderSalesSummaryRepository $orderSummaryRepository,
        private ProductSalesSummaryRepository $productSummaryRepository,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private UserRepository $userRepository,
        private ProductSalesRepository $productSalesRepository,
        private SidebarBadgeProvider $badgeProvider,
    ) {
    }

    /** @return array<string, mixed> */
    public function getData(): array
    {
        $badges = $this->badgeProvider->getCounts();

        return [
            'greeting' => $this->getGreeting(),
            'orderSummary' => $this->orderSummaryRepository->findOrderSalesSummary(SalesDuration::TODAY) ?? [],
            'orderCompare' => $this->orderSummaryRepository->findOrderSalesSummary(SalesDuration::WEEK_AGO) ?? [],
            'productSummary' => $this->productSummaryRepository->findProductSalesSummary(1, SalesType::ALL, SalesDuration::TODAY) ?? [],
            'productCompare' => $this->productSummaryRepository->findProductSalesSummary(1, SalesType::ALL, SalesDuration::WEEK_AGO) ?? [],
            'badges' => $badges,
            'totalActions' => $badges['pendingOrders'] + $badges['overdueOrders'] + $badges['rejectedPos'] + $badges['pendingReviews'],
            'latestOrders' => $this->orderRepository->findLatestOrders(
                new \DateTime(SalesDuration::TODAY->getStartDate()),
                5
            ),
            'topProducts' => $this->productSalesRepository->findLatestProductSales(
                ProductSalesType::create(SalesType::ALL, SalesDuration::TODAY),
                5
            ),
            'totalOrders' => $this->orderRepository->count(),
            'totalCustomers' => $this->userRepository->count(),
            'totalProducts' => $this->productRepository->count(),
            'totalCategories' => $this->categoryRepository->count(),
        ];
    }

    private function getGreeting(): string
    {
        $hour = (int) date('H');

        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    }
}
