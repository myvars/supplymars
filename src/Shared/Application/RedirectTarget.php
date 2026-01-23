<?php

namespace App\Shared\Application;

final readonly class RedirectTarget
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $route,
        public array $params = [],
        public bool $redirectRefresh = false,
        public int $redirectStatus = 303,
    ) {
    }
}
