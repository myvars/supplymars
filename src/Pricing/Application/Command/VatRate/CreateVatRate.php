<?php

declare(strict_types=1);

namespace App\Pricing\Application\Command\VatRate;

final readonly class CreateVatRate
{
    /**
     * @param numeric-string $rate
     */
    public function __construct(
        public string $name,
        public string $rate,
    ) {
    }
}
