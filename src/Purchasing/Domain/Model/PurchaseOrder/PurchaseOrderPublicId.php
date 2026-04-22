<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Model\PurchaseOrder;

use App\Shared\Domain\ValueObject\AbstractUlidId;

final readonly class PurchaseOrderPublicId extends AbstractUlidId
{
    // Inherits strict validation and factories.
}
