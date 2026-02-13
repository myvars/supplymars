<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesSummaryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(OrderSalesSummaryDoctrineRepository::class)]
interface OrderSalesSummaryRepository
{
    public function add(OrderSalesSummary $orderSalesSummary): void;

    public function deleteByOrderSalesType(OrderSalesType $orderSalesType): void;
}
