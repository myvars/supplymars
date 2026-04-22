<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class PriceFormatExtensionRuntime implements RuntimeExtensionInterface
{
    public function priceRounded(float|int|null $value, int $decimals = 0, string $symbol = '£'): string
    {
        $rounded = floor($value ?? 0);

        return $symbol . number_format($rounded, $decimals, '.', ',');
    }
}
