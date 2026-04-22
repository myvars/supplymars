<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Command\Supplier;

use App\Purchasing\Domain\Model\Supplier\SupplierColourScheme;
use App\Purchasing\Domain\Model\Supplier\SupplierPublicId;

final readonly class UpdateSupplier
{
    public function __construct(
        public SupplierPublicId $id,
        public string $name,
        public bool $isActive,
        public SupplierColourScheme $colourScheme = SupplierColourScheme::Violet,
    ) {
    }
}
