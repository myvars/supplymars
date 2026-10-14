<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use App\Shared\UI\Twig\StatusColor;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class StatusBadge
{
    public string $status;

    public bool $showIcon = false;

    public bool $interactive = false;

    public function getColorClasses(): string
    {
        return StatusColor::badgeClasses(StatusColor::resolve($this->status));
    }
}
