<?php

namespace App\Reporting\UI\Http\Chart;

use App\Order\Domain\Model\Order\OrderStatus;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ChartColorProviderInterface::class)]
final readonly class OrderStatusColorProvider implements ChartColorProviderInterface
{
    public function getColor(string|int $key): string
    {
        return OrderStatus::from($key)->getChartColor();
    }

    public function getSortOrder(string|int $key): int
    {
        return OrderStatus::from($key)->getLevel();
    }
}
