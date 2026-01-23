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

    public function process(bool $rebuild = false): void
    {
        foreach (SalesDuration::cases() as $salesDuration) {
            $this->processOrderSalesType(OrderSalesType::create($salesDuration, $rebuild));
        }
    }

    private function processOrderSalesType(OrderSalesType $orderSalesType): void
    {
        $sales = $this->getOrderSalesSummary($orderSalesType);

        $this->removeExistingSummary($orderSalesType);

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

            $this->em->persist($orderSalesSummary);
        }

        $this->em->flush();
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
