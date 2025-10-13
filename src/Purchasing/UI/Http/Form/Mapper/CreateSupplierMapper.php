<?php

namespace App\Purchasing\UI\Http\Form\Mapper;

use App\Purchasing\Application\Command\Supplier\CreateSupplier;
use App\Purchasing\UI\Http\Form\Model\SupplierForm;

final class CreateSupplierMapper
{
    public function __invoke(SupplierForm $data): CreateSupplier
    {
        return new CreateSupplier(
            $data->name,
            $data->isActive,
        );
    }
}
