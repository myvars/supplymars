<?php

namespace App\Reporting\Application\Handler;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use App\Reporting\Domain\Repository\OrderSalesRepository;
use App\Reporting\Domain\Repository\OrderSalesSummaryRepository;
use App\Shared\Application\FlusherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateOrderSalesSummaryHandler
{
    public function __construct(
        private OrderSalesRepository $orderSalesRepository,
        private OrderSalesSummaryRepository $orderSalesSummaryRepository,
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
            $results[$salesDuration->value] = $this->processOrderSalesType(
                OrderSalesType::create($salesDuration, $rebuild),
                $dryRun
            );
        }

        return $results;
    }

    private function processOrderSalesType(OrderSalesType $orderSalesType, bool $dryRun = false): int
    {
        $sales = $this->orderSalesRepository->findOrderSalesSummary($orderSalesType);

        if (!$dryRun) {
            $this->orderSalesSummaryRepository->deleteByOrderSalesType($orderSalesType);
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
                $this->orderSalesSummaryRepository->add($orderSalesSummary);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        return $processed;
    }
}
