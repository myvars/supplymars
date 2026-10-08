<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use App\Shared\UI\Twig\StatusColor;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class EntityCard
{
    public ?string $title = null;

    public string $colour = 'gray';

    public string $padding = 'p-3';

    public ?string $borderColour = null;

    public string $width = 'w-full';

    public string $layout = 'vertical';

    public ?string $editLink = null;

    public ?string $showLink = null;

    public string $editIcon = 'bi:pencil-square';

    public ?string $statusHighlight = null;

    public function getBackgroundClasses(): string
    {
        return match ($this->colour) {
            'gray' => 'bg-card',
            'green' => 'bg-linear-to-tr from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950',
            'supplier1' => 'bg-supplier1-50 dark:bg-supplier1-400/[0.14]',
            'supplier2' => 'bg-supplier2-50 dark:bg-supplier2-400/[0.14]',
            'supplier3' => 'bg-supplier3-50 dark:bg-supplier3-400/[0.14]',
            'supplier4' => 'bg-supplier4-50 dark:bg-supplier4-400/[0.14]',
            default => throw new \LogicException(sprintf('Unknown colourScheme "%s"', $this->colour)),
        };
    }

    public function getBorderClasses(): string
    {
        return match ($this->borderColour) {
            'gray', 'supplier1', 'supplier2', 'supplier3', 'supplier4', null => 'border border-border',
            'green' => 'border-2 border-green-200 dark:border-green-500/30',
            'red' => 'border-2 border-red-200 dark:border-red-500/30',
            default => throw new \LogicException(sprintf('Unknown colourScheme "%s"', $this->borderColour)),
        };
    }

    public function getEditLinkClasses(): string
    {
        return match ($this->colour) {
            'gray', 'supplier1', 'supplier2', 'supplier3', 'supplier4' => 'border bg-white border-gray-200 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600',
            'green' => 'border bg-white border-green-300 hover:bg-green-100 dark:border-green-700 dark:bg-green-900 dark:hover:bg-green-800',
            default => throw new \LogicException(sprintf('Unknown colourScheme "%s"', $this->colour)),
        };
    }

    public function getHighlightClasses(): string
    {
        if ($this->statusHighlight === null) {
            return '';
        }

        return StatusColor::stripeClasses(StatusColor::resolve($this->statusHighlight));
    }
}
