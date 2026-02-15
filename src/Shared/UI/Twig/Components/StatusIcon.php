<?php

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class StatusIcon
{
    public string $type = 'created';

    public function getIconName(): string
    {
        return match ($this->type) {
            'created' => 'bi:bag-check',
            'pending' => 'bi:hourglass-split',
            'processing' => 'flowbite:cog-solid',
            'accepted', 'active', 'verified' => 'bi:check-circle-fill',
            'rejected', 'inactive', 'unverified' => 'bi:x-lg',
            'refunded' => 'bi:arrow-return-left',
            'shipped' => 'bi:truck',
            'delivered' => 'bi:box-seam-fill',
            'cancelled' => 'bi:x-circle',
            default => throw new \LogicException(sprintf('Unknown icon "%s"', $this->type)),
        };
    }
}
