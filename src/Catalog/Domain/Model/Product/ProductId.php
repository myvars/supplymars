<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model\Product;

use App\Shared\Domain\ValueObject\AbstractIntId;

final readonly class ProductId extends AbstractIntId
{
    // Inherits strict validation and factories.
}
