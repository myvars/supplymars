<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class StatusIcon
{
    public string $type = 'created';

    public function getIconName(): string
    {
        return match ($this->type) {
            'created' => 'lets-icons:order',
            'pending' => 'bi:arrow-clockwise',
            'processing' => 'clarity:cog-solid',
            'accepted' => 'hugeicons:tick-01',
            'rejected' => 'bi:x-lg',
            'refunded' => 'lets-icons:refund-back',
            'shipped' => 'hugeicons:delivery-truck-01',
            'delivered' => 'ri:box-3-fill',
            'cancelled' => 'clarity:cancel-line',
            default => throw new \LogicException(sprintf('Unknown icon "%s"', $this->type)),
        };
    }
}
