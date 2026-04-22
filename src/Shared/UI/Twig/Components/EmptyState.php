<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class EmptyState
{
    public string $icon;

    public string $message;

    public ?string $subtitle = null;
}
