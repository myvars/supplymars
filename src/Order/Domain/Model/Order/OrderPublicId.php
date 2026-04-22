<?php

declare(strict_types=1);

namespace App\Order\Domain\Model\Order;

use App\Shared\Domain\ValueObject\AbstractUlidId;

final readonly class OrderPublicId extends AbstractUlidId
{
    // Inherits strict validation and factories.
}
