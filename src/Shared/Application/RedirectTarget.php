<?php

declare(strict_types=1);

namespace App\Shared\Application;

final readonly class RedirectTarget
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
}
