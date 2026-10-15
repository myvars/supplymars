<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class RedisHealthCheck implements HealthCheckInterface
{
    public function __construct(
        #[Autowire(service: 'snc_redis.default')]
        private \Redis $redis,
    ) {
    }

    public function name(): string
    {
        return 'redis';
    }

    public function check(): void
    {
        if ($this->redis->ping() === false) {
            throw new \RuntimeException('Redis did not answer the ping.');
        }
    }
}
