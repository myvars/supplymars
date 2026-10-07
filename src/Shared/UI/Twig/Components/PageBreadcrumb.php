<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class PageBreadcrumb
{
    public string $href;

    public string $label;

    public string $current;

    /** Frame id of an inline edit whose saved value should replace the current page text. */
    public ?string $mirrors = null;
}
