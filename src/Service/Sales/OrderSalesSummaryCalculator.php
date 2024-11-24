<?php

namespace App\Service\Sales;

use App\DTO\OrderSalesTypeDto;
use App\Entity\OrderSales;
use App\Entity\OrderSalesSummary;
use App\Enum\SalesDuration;
use Doctrine\ORM\EntityManagerInterface;

class OrderSalesSummaryCalculator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function process(bool $rebuild = false): void
    {
        foreach (SalesDuration::cases() as $duration) {
            $this->processOrderSalesType(OrderSalesTypeDto::create($duration, $rebuild));
        }
    }

    public function processOrderSalesType(OrderSalesTypeDto $dto): void
    {
        $sales = $this->getSales($dto);

        $this->removeExistingSummary($dto);

        foreach ($sales as $sale) {
            $orderSalesSummary = OrderSalesSummary::create(
                $dto->getDuration(),
                $sale['dateString'],
                $sale['orderCount'],
                $sale['orderValue'],
            );
            $this->entityManager->persist($orderSalesSummary);
        }

        $this->entityManager->flush();
    }

    public function getSales(OrderSalesTypeDto $dto): ?array
    {
        return $this->entityManager->getRepository(OrderSales::class)->calculateSales(
            $dto->getStartDate(),
            $dto->getEndDate(),
            $dto->getDateString()
        );
    }

    public function removeExistingSummary(OrderSalesTypeDto $dto): void
    {
        $this->entityManager->getRepository(OrderSalesSummary::class)->deleteByDuration(
            $dto->getDuration()->value,
            $dto->getRangeStartDate()
        );
    }
}