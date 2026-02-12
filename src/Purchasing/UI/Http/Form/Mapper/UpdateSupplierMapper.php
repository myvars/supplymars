<?php

namespace App\Purchasing\UI\Http\Form\Mapper;

use App\Purchasing\Application\Command\Supplier\UpdateSupplier;
use App\Purchasing\Domain\Model\Supplier\SupplierPublicId;
use App\Purchasing\UI\Http\Form\Model\SupplierForm;

final class UpdateSupplierMapper
{
    public function __invoke(SupplierForm $data): UpdateSupplier
    {
        return new UpdateSupplier(
            SupplierPublicId::fromString($data->id),
            $data->name,
            $data->isActive,
            $data->colourScheme,
        );
    }
}
