<?php

namespace App\Reporting\Application\Handler;

use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Reporting\Domain\Model\SalesType\CustomerActivitySales;
use App\Reporting\Domain\Model\SalesType\CustomerSales;
use App\Reporting\Domain\Repository\CustomerActivitySalesRepository;
use App\Reporting\Domain\Repository\CustomerSalesRepository;
use App\Shared\Application\FlusherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateCustomerSalesHandler
{
    public function __construct(
        private CustomerSalesRepository $customerSalesRepository,
        private CustomerActivitySalesRepository $customerActivitySalesRepository,
        private CustomerOrderDoctrineRepository $orderRepository,
        private UserDoctrineRepository $userRepository,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function process(string $date, bool $dryRun = false): int
    {
        $salesCount = $this->processCustomerSales($date, $dryRun);
        $activityCount = $this->processCustomerActivity($date, $dryRun);

        return $salesCount + $activityCount;
    }

    private function processCustomerSales(string $date, bool $dryRun = false): int
    {
        $sales = $this->orderRepository
            ->findCustomerSalesByDate(new \DateTime($date), new \DateTime($date)->modify('+ 1 day'));

        if (!$dryRun) {
            $this->customerSalesRepository->deleteByDate($date);
        }

        $processed = 0;
        foreach ($sales as $sale) {
            $customerSales = CustomerSales::create(
                (int) $sale['customerId'],
                $date,
                (int) $sale['orderCount'],
                $sale['orderValue'] ?? '0.00',
                (int) ($sale['itemCount'] ?? 0),
            );

            $errors = $this->validator->validate($customerSales);
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }

            if (!$dryRun) {
                $this->customerSalesRepository->add($customerSales);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        return $processed;
    }

    private function processCustomerActivity(string $date, bool $dryRun = false): int
    {
        $startDate = new \DateTime($date);
        $endDate = new \DateTime($date)->modify('+ 1 day');

        $activity = $this->orderRepository
            ->findCustomerActivityByDate($startDate, $endDate);

        if (!$dryRun) {
            $this->customerActivitySalesRepository->deleteByDate($date);
        }

        $totalCustomers = $this->userRepository->countNonStaffCustomers();

        $customerActivity = CustomerActivitySales::create(
            $date,
            $totalCustomers,
            (int) ($activity['activeCustomers'] ?? 0),
            (int) ($activity['newCustomers'] ?? 0),
            (int) ($activity['returningCustomers'] ?? 0),
        );

        $errors = $this->validator->validate($customerActivity);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        if (!$dryRun) {
            $this->customerActivitySalesRepository->add($customerActivity);
            $this->flusher->flush();
        }

        return 1;
    }
}
