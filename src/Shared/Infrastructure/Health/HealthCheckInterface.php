<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * One dependency the /health endpoint reports on. Every implementation is picked up automatically.
 */
#[AutoconfigureTag]
interface HealthCheckInterface
{
    public function name(): string;

    /**
     * @throws \Throwable when the dependency cannot be reached
     */
    public function check(): void;
}
