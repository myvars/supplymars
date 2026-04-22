<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model\Product;

use App\Shared\Domain\ValueObject\AbstractUlidId;

final readonly class ProductPublicId extends AbstractUlidId
{
    // Inherits strict validation and factories.
}
