<?php

namespace App\Pricing\UI\Http\Form\Mapper;

use App\Pricing\Application\Command\VatRate\CreateVatRate;
use App\Pricing\UI\Http\Form\Model\VatRateForm;

final class CreateVatRateMapper
{
    public function __invoke(VatRateForm $data): CreateVatRate
    {
        return new CreateVatRate(
            $data->name,
            $data->rate,
        );
    }
}
