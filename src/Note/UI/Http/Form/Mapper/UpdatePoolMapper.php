<?php

namespace App\Note\UI\Http\Form\Mapper;

use App\Note\Application\Command\Pool\UpdatePool;
use App\Note\Domain\Model\Pool\PoolPublicId;
use App\Note\UI\Http\Form\Model\PoolForm;

final class UpdatePoolMapper
{
    public function __invoke(PoolForm $data): UpdatePool
    {
        return new UpdatePool(
            PoolPublicId::fromString($data->id),
            $data->name,
            $data->description,
            $data->isActive,
            $data->isCustomerVisible,
        );
    }
}
