<?php

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Button
{
    public string $variant = 'primary';

    public string $tag = 'button';

    public function getVariantClasses(): string
    {
        return match ($this->variant) {
            'primary' => 'px-3 text-white bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:outline-hidden focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800',
            'secondary' => 'px-3 text-white bg-gray-600 hover:bg-gray-700 focus:ring-2 focus:outline-hidden focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800',
            'alternative' => 'px-3 text-gray-900 bg-white focus:outline-hidden border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700',
            'success' => 'px-3 text-white bg-green-600 hover:bg-green-700 focus:ring-2 focus:outline-hidden focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800',
            'danger' => 'px-3 text-white bg-red-600 hover:bg-red-700 focus:ring-2 focus:outline-hidden focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800',
            'warning' => 'px-3 text-white bg-yellow-600 hover:bg-yellow-700 focus:ring-2 focus:outline-hidden focus:ring-yellow-300 dark:bg-yellow-600 dark:hover:bg-yellow-700 dark:focus:ring-yellow-800',
            'link' => 'text-white',
            default => throw new \LogicException(sprintf('Unknown button type "%s"', $this->variant)),
        };
    }
}
