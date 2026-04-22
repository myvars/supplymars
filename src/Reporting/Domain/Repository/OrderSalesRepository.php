<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Model\SalesType\OrderSales;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(OrderSalesDoctrineRepository::class)]
interface OrderSalesRepository
{
    public function add(OrderSales $orderSales): void;

    public function deleteByDate(string $date): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findOrderSalesSummary(OrderSalesType $orderSalesType): array;
}
