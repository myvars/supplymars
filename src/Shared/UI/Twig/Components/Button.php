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
            'primary' => 'px-4 text-white bg-blue-600 hover:bg-blue-500 shadow-sm hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:bg-blue-500 dark:hover:bg-blue-400 dark:focus-visible:outline-blue-400',
            'secondary' => 'px-4 text-white bg-gray-600 hover:bg-gray-500 shadow-sm hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600 dark:bg-gray-500 dark:hover:bg-gray-400 dark:focus-visible:outline-gray-400',
            'alternative' => 'px-4 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 shadow-sm hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white dark:focus-visible:outline-gray-500',
            'success' => 'px-4 text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:focus-visible:outline-emerald-400',
            'danger' => 'px-4 text-white bg-red-600 hover:bg-red-500 shadow-sm hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 dark:bg-red-500 dark:hover:bg-red-400 dark:focus-visible:outline-red-400',
            'warning' => 'px-4 text-white bg-amber-600 hover:bg-amber-500 shadow-sm hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600 dark:bg-amber-500 dark:hover:bg-amber-400 dark:focus-visible:outline-amber-400',
            'link' => 'text-gray-700 underline-offset-2 hover:text-gray-900 hover:underline focus-visible:outline-hidden focus-visible:underline dark:text-gray-300 dark:hover:text-white',
            default => throw new \LogicException(sprintf('Unknown button type "%s"', $this->variant)),
        };
    }
}
