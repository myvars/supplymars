<?php

namespace App\Pricing\Application\Command\VatRate;

use App\Pricing\Domain\Model\VatRate\VatRatePublicId;

final readonly class UpdateVatRate
{
    /**
     * @param numeric-string $rate
     */
    public function __construct(
        public VatRatePublicId $id,
        public string $name,
        public string $rate,
    ) {
    }
}
