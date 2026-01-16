<?php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Manufacturer\CreateManufacturer;
use App\Catalog\UI\Http\Form\Model\ManufacturerForm;

final class CreateManufacturerMapper
{
    public function __invoke(ManufacturerForm $data): CreateManufacturer
    {
        return new CreateManufacturer(
            $data->name,
            $data->isActive,
        );
    }
}
