<?php

namespace App\Reporting\Application\Handler;

use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Reporting\Domain\Model\SalesType\CustomerActivitySales;
use App\Reporting\Domain\Model\SalesType\CustomerSales;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateCustomerSalesHandler
{
    public function __construct(
        private EntityManagerInterface $em,
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
        $sales = $this->em->getRepository(CustomerOrder::class)
            ->findCustomerSalesByDate(new \DateTime($date), new \DateTime($date)->modify('+ 1 day'));

        if (!$dryRun) {
            $this->em->getRepository(CustomerSales::class)->deleteByDate($date);
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
                $this->em->persist($customerSales);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        return $processed;
    }

    private function processCustomerActivity(string $date, bool $dryRun = false): int
    {
        $startDate = new \DateTime($date);
        $endDate = new \DateTime($date)->modify('+ 1 day');

        $activity = $this->em->getRepository(CustomerOrder::class)
            ->findCustomerActivityByDate($startDate, $endDate);

        if (!$dryRun) {
            $this->em->getRepository(CustomerActivitySales::class)->deleteByDate($date);
        }

        $totalCustomers = $this->em->getRepository(User::class)->countNonStaffCustomers();

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
            $this->em->persist($customerActivity);
            $this->em->flush();
        }

        return 1;
    }
}
