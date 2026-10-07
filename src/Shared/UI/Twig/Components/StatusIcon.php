<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class StatusIcon
{
    public string $type = 'created';

    /** Null when the type has no icon, so a status without one renders nothing. */
    public function getIconName(): ?string
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
            default => null,
        };
    }
}
