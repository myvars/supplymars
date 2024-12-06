<?php

namespace App\Service\Sales;

use App\Entity\CustomerOrder;
use App\Entity\OrderSales;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class OrderSalesCalculator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function process(string $date): void
    {
        $sales = $this->getOrderSales($date);

        $this->removeExistingOrderSales($date);

        foreach ($sales as $sale) {
            $orderSales = OrderSales::create(
                $date,
                $sale['orderCount'],
                $sale['orderValue'],
                $sale['averageOrderValue']
            );
            $this->entityManager->persist($orderSales);
        }

        $this->entityManager->flush();
    }

    private function getOrderSales(string $date): array
    {
        return $this->entityManager->getRepository(CustomerOrder::class)
            ->findOrderSalesByDate(new DateTime($date), (new DateTime($date))->modify('+ 1 day'));
    }

    private function removeExistingOrderSales(string $date): void
    {
        $this->entityManager->getRepository(OrderSales::class)
            ->deleteByDate($date);
    }
}