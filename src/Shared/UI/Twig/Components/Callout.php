<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Callout
{
    public string $type = 'info';

    public function getVariant(): string
    {
        return match ($this->type) {
            'danger' => 'destructive',
            'warning' => 'warning',
            'success' => 'success',
            default => 'info',
        };
    }

    public function getIconName(): string
    {
        return match ($this->type) {
            'danger' => 'bi:exclamation-circle',
            'warning' => 'bi:exclamation-triangle',
            'success' => 'bi:check-circle',
            default => 'bi:info-circle',
        };
    }
}
