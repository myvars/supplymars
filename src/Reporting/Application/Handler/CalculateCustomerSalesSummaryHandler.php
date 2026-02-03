<?php

namespace App\Reporting\Application\Handler;

use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Reporting\Domain\Metric\CustomerSegment;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerGeographicSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Model\SalesType\CustomerSegmentSummary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateCustomerSalesSummaryHandler
{
    public function __construct(
        private EntityManagerInterface $em,
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

        $orderRepo = $this->em->getRepository(CustomerOrder::class);
        $activity = $orderRepo->findCustomerActivityByDate($startDate, $endDate);

        $totalCustomers = $this->em->getRepository(User::class)->countNonStaffCustomers();
        $activeCustomers = (int) ($activity['activeCustomers'] ?? 0);
        $newCustomers = (int) ($activity['newCustomers'] ?? 0);
        $returningCustomers = (int) ($activity['returningCustomers'] ?? 0);

        // Calculate revenue metrics
        $revenueData = $orderRepo->findRevenueMetrics($startDate, $endDate);
        $totalRevenue = $revenueData['totalRevenue'] ?? '0.00';
        $averageAov = $revenueData['averageAov'] ?? '0.00';

        // Calculate CLV (lifetime revenue / total customers)
        $lifetimeRevenue = $orderRepo->findLifetimeRevenue();
        $averageClv = $totalCustomers > 0
            ? bcdiv($lifetimeRevenue, (string) $totalCustomers, 2)
            : '0.00';

        // Repeat rate
        $repeatRate = $orderRepo->findRepeatRate();

        // Review rate
        $reviewRate = $orderRepo->findReviewRate($startDate, $endDate, $activeCustomers);

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
            $this->em->getRepository(CustomerSalesSummary::class)->deleteByCustomerSalesType($customerSalesType);
            $this->em->persist($summary);
            $this->em->flush();
        }

        return 1;
    }

    private function processGeographicSummary(CustomerSalesType $customerSalesType, bool $dryRun = false): int
    {
        $startDate = new \DateTime($customerSalesType->getStartDate());
        $endDate = new \DateTime($customerSalesType->getEndDate());

        $geoData = $this->em->getRepository(CustomerOrder::class)
            ->findCustomerGeographicSales($startDate, $endDate);

        if (!$dryRun) {
            $this->em->getRepository(CustomerGeographicSummary::class)->deleteByCustomerSalesType($customerSalesType);
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
                $this->em->persist($summary);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        return $processed;
    }

    private function processSegmentSummary(CustomerSalesType $customerSalesType, bool $dryRun = false): int
    {
        $startDate = new \DateTime($customerSalesType->getStartDate());
        $endDate = new \DateTime($customerSalesType->getEndDate());

        $segmentData = $this->em->getRepository(CustomerOrder::class)
            ->findCustomerSegmentSales($startDate, $endDate);

        if (!$dryRun) {
            $this->em->getRepository(CustomerSegmentSummary::class)->deleteByCustomerSalesType($customerSalesType);
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
                $this->em->persist($summary);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        return $processed;
    }
}
