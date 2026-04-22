<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Chart;

interface ChartColorProviderInterface
{
    public function getColor(string|int $key): string;

    /**
     * @return int|null Sort order for the key, or null if no sorting needed
     */
    public function getSortOrder(string|int $key): ?int;
}
