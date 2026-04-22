<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\Manufacturer;

final readonly class CreateManufacturer
{
    public function __construct(
        public string $name,
        public bool $isActive,
    ) {
    }
}
