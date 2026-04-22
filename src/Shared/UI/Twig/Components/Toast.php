<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Toast
{
    public string $type = 'success';

    public function getTypeClasses(): string
    {
        return match ($this->type) {
            'success' => 'text-green-500 bg-green-100 dark:bg-green-800 dark:text-green-200',
            'info' => 'text-blue-500 bg-blue-100 dark:bg-blue-800 dark:text-blue-200',
            'danger' => 'text-red-500 bg-red-100 dark:bg-red-800 dark:text-red-200',
            'warning' => 'text-orange-500 bg-orange-100 dark:bg-orange-800 dark:text-orange-200',
            default => throw new \LogicException(sprintf('Unknown toast type "%s"', $this->type)),
        };
    }

    public function getIconName(): string
    {
        return match ($this->type) {
            'success' => 'flowbite:check-circle-solid',
            'info' => 'flowbite:info-circle-solid',
            'danger' => 'flowbite:close-circle-solid',
            'warning' => 'flowbite:exclamation-circle-solid',
            default => throw new \LogicException(sprintf('Unknown toast icon "%s"', $this->type)),
        };
    }

    public function getTimerbarColor(): string
    {
        return match ($this->type) {
            'success' => 'bg-green-500',
            'info' => 'bg-blue-500',
            'danger' => 'bg-red-500',
            'warning' => 'bg-orange-500',
            default => throw new \LogicException(sprintf('Unknown Timerbar color "%s"', $this->type)),
        };
    }

    public function getDuration(): int
    {
        return match ($this->type) {
            'success' => 3500,
            'info' => 5000,
            'danger' => 6000,
            'warning' => 6000,
            default => 3500,
        };
    }
}
