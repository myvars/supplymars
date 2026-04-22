<?php

declare(strict_types=1);

namespace App\Note\Application\Command\Pool;

use App\Note\Domain\Model\Pool\PoolPublicId;

final readonly class DeletePool
{
    public function __construct(public PoolPublicId $id)
    {
    }
}
