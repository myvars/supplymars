<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use Doctrine\DBAL\Connection;

final readonly class DatabaseHealthCheck implements HealthCheckInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function name(): string
    {
        return 'database';
    }

    public function check(): void
    {
        $this->connection->executeQuery('SELECT 1');
    }
}
