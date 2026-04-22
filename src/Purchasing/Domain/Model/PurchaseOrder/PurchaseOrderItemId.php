<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Model\PurchaseOrder;

use App\Shared\Domain\ValueObject\AbstractIntId;

final readonly class PurchaseOrderItemId extends AbstractIntId
{
    // Inherits strict validation and factories.
}
