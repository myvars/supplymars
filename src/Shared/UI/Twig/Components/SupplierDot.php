<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SupplierDot
{
    public string $colour = 'supplier1';

    public function getColorClasses(): string
    {
        return match ($this->colour) {
            'supplier1' => 'bg-supplier1-500',
            'supplier2' => 'bg-supplier2-500',
            'supplier3' => 'bg-supplier3-500',
            'supplier4' => 'bg-supplier4-500',
            default => 'bg-gray-400',
        };
    }
}
