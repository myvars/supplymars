<?php

namespace App\Note\Application\Command\Pool;

use App\Note\Domain\Model\Pool\PoolPublicId;

final readonly class UpdatePool
{
    public function __construct(
        public PoolPublicId $id,
        public string $name,
        public ?string $description,
        public bool $isActive,
        public bool $isCustomerVisible,
    ) {
    }
}
