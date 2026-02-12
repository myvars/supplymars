<?php

namespace App\Purchasing\Application\Command\Supplier;

use App\Purchasing\Domain\Model\Supplier\SupplierColourScheme;

final readonly class CreateSupplier
{
    public function __construct(
        public string $name,
        public bool $isActive,
        public SupplierColourScheme $colourScheme = SupplierColourScheme::Violet,
    ) {
    }
}
