<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Order\Domain\Model\Order\OrderPublicId;

final readonly class AllocateOrder
{
    public function __construct(public OrderPublicId $id)
    {
    }
}
