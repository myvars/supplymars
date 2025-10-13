<?php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Manufacturer\UpdateManufacturer;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;
use App\Catalog\UI\Http\Form\Model\ManufacturerForm;

final class UpdateManufacturerMapper
{
    public function __invoke(ManufacturerForm $data): UpdateManufacturer
    {
        return new UpdateManufacturer(
            ManufacturerPublicId::fromString($data->id),
            $data->name,
            $data->isActive
        );
    }
}

