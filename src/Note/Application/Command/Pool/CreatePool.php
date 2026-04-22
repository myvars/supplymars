<?php

declare(strict_types=1);

namespace App\Note\Application\Command\Pool;

final readonly class CreatePool
{
    public function __construct(
        public string $name,
        public ?string $description,
        public bool $isActive,
        public bool $isCustomerVisible,
    ) {
    }
}
