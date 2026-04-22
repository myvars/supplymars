<?php

declare(strict_types=1);

namespace App\Pricing\Application\Command\VatRate;

use App\Pricing\Domain\Model\VatRate\VatRatePublicId;

final readonly class DeleteVatRate
{
    public function __construct(public VatRatePublicId $id)
    {
    }
}
