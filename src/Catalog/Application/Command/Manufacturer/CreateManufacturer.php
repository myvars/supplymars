<?php

namespace App\Catalog\Application\Command\Manufacturer;

final readonly class CreateManufacturer
{
    public function __construct(
        public string $name,
        public bool $isActive,
    ) {
    }
}
