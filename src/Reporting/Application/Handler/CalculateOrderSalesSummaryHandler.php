<?php

namespace App\Reporting\Application\Handler;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSales;
use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateOrderSalesSummaryHandler
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
            $results[$salesDuration->value] = $this->processOrderSalesType(
                OrderSalesType::create($salesDuration, $rebuild),
                $dryRun
            );
        }

        return $results;
    }

    private function processOrderSalesType(OrderSalesType $orderSalesType, bool $dryRun = false): int
    {
        $sales = $this->getOrderSalesSummary($orderSalesType);

        if (!$dryRun) {
            $this->removeExistingSummary($orderSalesType);
        }

        $processed = 0;
        foreach ($sales as $sale) {
            $orderSalesSummary = OrderSalesSummary::create(
                $orderSalesType,
                $sale['dateString'],
                $sale['orderCount'],
                $sale['orderValue'],
                $sale['averageOrderValue']
            );

            $errors = $this->validator->validate($orderSalesSummary);
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }

            if (!$dryRun) {
                $this->em->persist($orderSalesSummary);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        return $processed;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getOrderSalesSummary(OrderSalesType $orderSalesType): array
    {
        return $this->em->getRepository(OrderSales::class)
            ->findOrderSalesSummary($orderSalesType);
    }

    private function removeExistingSummary(OrderSalesType $orderSalesType): void
    {
        $this->em->getRepository(OrderSalesSummary::class)
            ->deleteByOrderSalesType($orderSalesType);
    }
}
