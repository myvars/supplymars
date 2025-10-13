<?php

namespace App\Catalog\Application\Command\Manufacturer;

use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;

final readonly class UpdateManufacturer
{
    public function __construct(
        public ManufacturerPublicId $id,
        public string $name,
        public bool $isActive,
    ) {
    }
}
