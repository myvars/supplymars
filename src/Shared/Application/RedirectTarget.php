<?php

declare(strict_types=1);

namespace App\Shared\Application;

use MyVars\FormFlow\Contract\RedirectTargetInterface;

final readonly class RedirectTarget implements RedirectTargetInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $route,
        public array $params = [],
        public int $redirectStatus = 303,
    ) {
    }

    public function route(): string
    {
        return $this->route;
    }

    /**
     * @return array<string, mixed>
     */
    public function params(): array
    {
        return $this->params;
    }

    public function status(): int
    {
        return $this->redirectStatus;
    }
}
