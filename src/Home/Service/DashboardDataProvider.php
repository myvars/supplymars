<?php

declare(strict_types=1);

namespace App\Home\Service;

use App\Catalog\Infrastructure\Persistence\Doctrine\CategoryDoctrineRepository;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesSummaryDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;
use App\Shared\Application\Service\SidebarBadgeProvider;

final readonly class DashboardDataProvider
{
    public function __construct(
        private OrderSalesSummaryDoctrineRepository $orderSummaryRepository,
        private ProductSalesSummaryDoctrineRepository $productSummaryRepository,
        private CustomerOrderDoctrineRepository $orderRepository,
        private ProductDoctrineRepository $productRepository,
        private CategoryDoctrineRepository $categoryRepository,
        private UserDoctrineRepository $userRepository,
        private ProductSalesDoctrineRepository $productSalesRepository,
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
