<?php

namespace App\Pricing\Application\Command\VatRate;

final readonly class CreateVatRate
{
    public function __construct(
        public string $name,
        public string $rate,
    ) {
    }
}
