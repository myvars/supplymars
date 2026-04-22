<?php

declare(strict_types=1);

namespace App\Purchasing\UI\Http\Chart;

use App\Purchasing\Domain\Model\Supplier\SupplierColourScheme;
use App\Reporting\UI\Http\Chart\ChartColorProviderInterface;

final readonly class SupplierColorProvider implements ChartColorProviderInterface
{
    public function getColor(string|int $key): string
    {
        foreach (SupplierColourScheme::cases() as $scheme) {
            if ($scheme->cssPrefix() === $key) {
                return $scheme->chartColor();
            }
        }

        return SupplierColourScheme::getDefault()->chartColor();
    }

    public function getSortOrder(string|int $key): ?int
    {
        return null;
    }
}
