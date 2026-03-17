<?php

namespace App\Shared\UI\Twig\Components;

use App\Shared\UI\Twig\StatusColor;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Card
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
            'gray' => 'bg-white dark:bg-gray-700/50',
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
            'gray', 'supplier1', 'supplier2', 'supplier3', 'supplier4', null => 'border border-gray-200 dark:border-gray-600',
            'green' => 'border border-2 border-green-200 dark:border-gray-600',
            'red' => 'border border-2 border-red-200 dark:border-gray-600',
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

        $color = StatusColor::resolve($this->statusHighlight);

        return match ($color) {
            'green' => 'border-l-4 border-l-green-500 dark:border-l-green-400',
            'blue' => 'border-l-4 border-l-blue-500 dark:border-l-blue-400',
            'emerald' => 'border-l-4 border-l-emerald-500 dark:border-l-emerald-400',
            'yellow' => 'border-l-4 border-l-yellow-500 dark:border-l-yellow-400',
            'orange' => 'border-l-4 border-l-orange-500 dark:border-l-orange-400',
            'purple' => 'border-l-4 border-l-purple-500 dark:border-l-purple-400',
            'red' => 'border-l-4 border-l-red-500 dark:border-l-red-400',
            default => 'border-l-4 border-l-gray-400 dark:border-l-gray-500',
        };
    }
}
