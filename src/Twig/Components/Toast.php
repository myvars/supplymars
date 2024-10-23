<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Toast
{
    public string $type = 'success';

    public function getTypeClasses(): string
    {
        return match ($this->type) {
            'success' => 'text-green-500 bg-green-100 dark:bg-green-800 dark:text-green-200',
            'danger' => 'text-red-500 bg-red-100 dark:bg-red-800 dark:text-red-200',
            'warning' => 'text-orange-500 bg-orange-100 dark:bg-orange-800 dark:text-orange-200',
            default => throw new \LogicException(sprintf('Unknown toast type "%s"', $this->type)),
        };
    }

    public function getToastIcon(): string
    {
        return match ($this->type) {
            'success' => '<svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/></svg>',
            'danger' => '<svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/></svg>',
            'warning' => '<svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM10 15a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-4a1 1 0 0 1-2 0V6a1 1 0 0 1 2 0v5Z"/></svg>',
            default => throw new \LogicException(sprintf('Unknown toast icon "%s"', $this->type)),
        };
    }

    public function getTimerbarColor(): string
    {
        return match ($this->type) {
            'success' => 'bg-green-500',
            'danger' => 'bg-red-500',
            'warning' => 'bg-orange-500',
            default => throw new \LogicException(sprintf('Unknown Timerbar color "%s"', $this->type)),
        };
    }
}
