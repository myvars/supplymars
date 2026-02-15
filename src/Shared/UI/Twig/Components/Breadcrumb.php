<?php

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Breadcrumb
{
    public string $href;

    public string $label;

    public string $current;
}
