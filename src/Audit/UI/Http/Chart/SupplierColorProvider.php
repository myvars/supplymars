<?php

namespace App\Audit\UI\Http\Chart;

final readonly class SupplierColorProvider
{
    private const array SCHEME_COLORS = [
        'supplier1' => '#06b6d4',
        'supplier2' => '#ec4899',
        'supplier3' => '#0e9f6e',
        'supplier4' => '#ae927d',
    ];

    public function getColor(string $colourScheme): string
    {
        return self::SCHEME_COLORS[$colourScheme] ?? self::SCHEME_COLORS['supplier1'];
    }
}
