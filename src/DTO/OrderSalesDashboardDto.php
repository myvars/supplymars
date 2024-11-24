<?php

namespace App\DTO;

use App\Enum\OrderSalesMetric;
use App\Enum\SalesDuration;

final class OrderSalesDashboardDto
{
    private string $sortDirection = 'desc';

    private OrderSalesMetric $sort = OrderSalesMetric::COUNT;

    private SalesDuration $duration = SalesDuration::LAST_30;


    public function getSort(): ?OrderSalesMetric
    {
        return $this->sort;
    }

    public function setSort(?string $sort): OrderSalesDashboardDto
    {
        if (!OrderSalesMetric::isValid($sort)) {
            $sort = OrderSalesMetric::default()->value;
        }

        $this->sort = OrderSalesMetric::from($sort);

        return $this;
    }

    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }

    public function setSortDirection(?string $sortDirection): OrderSalesDashboardDto
    {
        if (!in_array(strtoupper((string) $sortDirection), ['ASC', 'DESC'])) {
            $sortDirection = 'DESC';
        }

        $this->sortDirection = strtolower((string) $sortDirection);

        return $this;
    }

    public function getDuration(): ?SalesDuration
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): OrderSalesDashboardDto
    {
        if (!SalesDuration::isValid($duration)) {
            $duration = SalesDuration::default()->value;
        }

        $this->duration = SalesDuration::from($duration);

        return $this;
    }
}