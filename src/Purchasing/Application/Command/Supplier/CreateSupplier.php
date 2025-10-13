<?php

namespace App\Purchasing\Application\Command\Supplier;

final readonly class CreateSupplier
{
    public function __construct(
        public string $name,
        public bool $isActive,
    ) {
    }
}
