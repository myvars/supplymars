<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\Manufacturer;

use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;

final readonly class DeleteManufacturer
{
    public function __construct(public ManufacturerPublicId $id)
    {
    }
}
