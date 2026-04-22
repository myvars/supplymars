<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model\Category;

use App\Shared\Domain\ValueObject\AbstractIntId;

final readonly class CategoryId extends AbstractIntId
{
    // Inherits strict validation and factories.
}
