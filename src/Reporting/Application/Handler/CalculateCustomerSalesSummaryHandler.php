<?php

namespace App\Reporting\Application\Handler;

use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Reporting\Domain\Metric\CustomerSegment;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerGeographicSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Model\SalesType\CustomerSegmentSummary;
use App\Reporting\Domain\Repository\CustomerGeographicSummaryRepository;
use App\Reporting\Domain\Repository\CustomerSalesSummaryRepository;
use App\Reporting\Domain\Repository\CustomerSegmentSummaryRepository;
use App\Shared\Application\FlusherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateCustomerSalesSummaryHandler
{
    public function __construct(
        private CustomerSalesSummaryRepository $customerSalesSummaryRepository,
        private CustomerGeographicSummaryRepository $customerGeographicSummaryRepository,
        private CustomerSegmentSummaryRepository $customerSegmentSummaryRepository,
        private CustomerOrderDoctrineRepository $orderRepository,
        private UserDoctrineRepository $userRepository,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function process(bool $rebuild = false, bool $dryRun = false): array
    {
        $results = [];
        foreach (SalesDuration::cases() as $salesDuration) {
            $customerSalesType = CustomerSalesType::create($salesDuration, $rebuild);
            $count = $this->processCustomerSalesSummary($customerSalesType, $dryRun);
            $count += $this->processGeographicSummary($customerSalesType, $dryRun);
            $count += $this->processSegmentSummary($customerSalesType, $dryRun);
            $results[$salesDuration->value] = $count;
        }

        return $results;
    }

    private function processCustomerSalesSummary(CustomerSalesType $customerSalesType, bool $dryRun = false): int
    {
        $startDate = new \DateTime($customerSalesType->getStartDate());
        $endDate = new \DateTime($customerSalesType->getEndDate());

        $activity = $this->orderRepository->findCustomerActivityByDate($startDate, $endDate);

        $totalCustomers = $this->userRepository->countNonStaffCustomers();
        $activeCustomers = (int) ($activity['activeCustomers'] ?? 0);
        $newCustomers = (int) ($activity['newCustomers'] ?? 0);
        $returningCustomers = (int) ($activity['returningCustomers'] ?? 0);

        // Calculate revenue metrics
        $revenueData = $this->orderRepository->findRevenueMetrics($startDate, $endDate);
        $totalRevenue = $revenueData['totalRevenue'] ?? '0.00';
        $averageAov = $revenueData['averageAov'] ?? '0.00';

        // Calculate CLV (lifetime revenue / total customers)
        $lifetimeRevenue = $this->orderRepository->findLifetimeRevenue();
        $averageClv = $totalCustomers > 0
            ? bcdiv($lifetimeRevenue, (string) $totalCustomers, 2)
            : '0.00';

        // Repeat rate (returning customers as percentage of active customers in period)
        $repeatRate = $activeCustomers > 0
            ? number_format(($returningCustomers / $activeCustomers) * 100, 2, '.', '')
            : '0.00';

        // Review rate
        $reviewRate = $this->orderRepository->findReviewRate($startDate, $endDate, $activeCustomers);

        // Average orders per customer
        $averageOrdersPerCustomer = $activeCustomers > 0
            ? bcdiv((string) ($revenueData['orderCount'] ?? 0), (string) $activeCustomers, 2)
            : '0.00';

        $dateString = $customerSalesType->getStartDate();
        $summary = CustomerSalesSummary::create(
            $customerSalesType,
            $dateString,
            $totalCustomers,
            $activeCustomers,
            $newCustomers,
            $returningCustomers,
            $totalRevenue,
            $averageClv,
            $averageAov,
            $repeatRate,
            $reviewRate,
            $averageOrdersPerCustomer,
        );

        $errors = $this->validator->validate($summary);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        if (!$dryRun) {
            $this->customerSalesSummaryRepository->deleteByCustomerSalesType($customerSalesType);
            $this->customerSalesSummaryRepository->add($summary);
            $this->flusher->flush();
        }

        return 1;
    }

    private function processGeographicSummary(CustomerSalesType $customerSalesType, bool $dryRun = false): int
    {
        $startDate = new \DateTime($customerSalesType->getStartDate());
        $endDate = new \DateTime($customerSalesType->getEndDate());

        $geoData = $this->orderRepository
            ->findCustomerGeographicSales($startDate, $endDate);

        if (!$dryRun) {
            $this->customerGeographicSummaryRepository->deleteByCustomerSalesType($customerSalesType);
        }

        $dateString = $customerSalesType->getStartDate();
        $processed = 0;

        foreach ($geoData as $geo) {
            $summary = CustomerGeographicSummary::create(
                $customerSalesType,
                $geo['city'],
                $dateString,
                (int) $geo['customerCount'],
                (int) $geo['orderCount'],
                $geo['orderValue'] ?? '0.00',
                $geo['averageOrderValue'] ?? '0.00',
            );

            $errors = $this->validator->validate($summary);
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }

            if (!$dryRun) {
                $this->customerGeographicSummaryRepository->add($summary);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        return $processed;
    }

    private function processSegmentSummary(CustomerSalesType $customerSalesType, bool $dryRun = false): int
    {
        $startDate = new \DateTime($customerSalesType->getStartDate());
        $endDate = new \DateTime($customerSalesType->getEndDate());

        $segmentData = $this->orderRepository
            ->findCustomerSegmentSales($startDate, $endDate);

        if (!$dryRun) {
            $this->customerSegmentSummaryRepository->deleteByCustomerSalesType($customerSalesType);
        }

        $dateString = $customerSalesType->getStartDate();
        $processed = 0;

        foreach ($segmentData as $seg) {
            $segment = CustomerSegment::tryFrom($seg['segment']);
            if ($segment === null) {
                continue;
            }

            $summary = CustomerSegmentSummary::create(
                $customerSalesType,
                $segment,
                $dateString,
                (int) $seg['customerCount'],
                (int) $seg['orderCount'],
                $seg['orderValue'] ?? '0.00',
                $seg['averageOrderValue'] ?? '0.00',
                $seg['averageItemsPerOrder'] ?? '0.00',
            );

            $errors = $this->validator->validate($summary);
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }

            if (!$dryRun) {
                $this->customerSegmentSummaryRepository->add($summary);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        return $processed;
    }
}
