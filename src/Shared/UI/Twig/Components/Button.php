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
            'primary' => 'px-4 text-white bg-blue-600 hover:bg-blue-500 shadow-sm hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-500 dark:hover:bg-blue-400 dark:focus:ring-blue-400 dark:focus:ring-offset-gray-900',
            'secondary' => 'px-4 text-white bg-gray-600 hover:bg-gray-500 shadow-sm hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-gray-500 dark:hover:bg-gray-400 dark:focus:ring-gray-400 dark:focus:ring-offset-gray-900',
            'alternative' => 'px-4 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 shadow-sm hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-500 dark:focus:ring-offset-gray-900',
            'success' => 'px-4 text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:focus:ring-emerald-400 dark:focus:ring-offset-gray-900',
            'danger' => 'px-4 text-white bg-red-600 hover:bg-red-500 shadow-sm hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:bg-red-500 dark:hover:bg-red-400 dark:focus:ring-red-400 dark:focus:ring-offset-gray-900',
            'warning' => 'px-4 text-white bg-amber-600 hover:bg-amber-500 shadow-sm hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:bg-amber-500 dark:hover:bg-amber-400 dark:focus:ring-amber-400 dark:focus:ring-offset-gray-900',
            'link' => 'text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white',
            default => throw new \LogicException(sprintf('Unknown button type "%s"', $this->variant)),
        };
    }
}
