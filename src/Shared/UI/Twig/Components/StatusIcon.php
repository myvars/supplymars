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
            'created' => 'lets-icons:order',
            'pending' => 'bi:hourglass-split',
            'processing' => 'clarity:cog-solid',
            'accepted', 'active', 'verified' => 'hugeicons:tick-01',
            'rejected', 'inactive', 'unverified' => 'bi:x-lg',
            'refunded' => 'lets-icons:refund-back',
            'shipped' => 'hugeicons:delivery-truck-01',
            'delivered' => 'ri:box-3-fill',
            'cancelled' => 'clarity:cancel-line',
            default => throw new \LogicException(sprintf('Unknown icon "%s"', $this->type)),
        };
    }
}
