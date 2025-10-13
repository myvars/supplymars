<?php

namespace App\Pricing\UI\Http\Form\Mapper;

use App\Pricing\Application\Command\VatRate\UpdateVatRate;
use App\Pricing\Domain\Model\VatRate\VatRatePublicId;
use App\Pricing\UI\Http\Form\Model\VatRateForm;

final class UpdateVatRateMapper
{
    public function __invoke(VatRateForm $data): UpdateVatRate
    {
        return new UpdateVatRate(
            VatRatePublicId::fromString($data->id),
            $data->name,
            $data->rate,
        );
    }
}
