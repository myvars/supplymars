<?php

namespace App\Shared\UI\Twig\Extension;

use App\Shared\UI\Twig\Runtime\PriceFormatExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PriceFormatExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('price_rounded', [PriceFormatExtensionRuntime::class, 'priceRounded']),
        ];
    }
}
