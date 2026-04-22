<?php

declare(strict_types=1);

namespace App\Audit\Domain\Model\StockChange;

use App\Shared\Domain\ValueObject\AbstractIntId;

final readonly class SupplierStockChangeLogId extends AbstractIntId
{
    // Inherits strict validation and factories.
}
