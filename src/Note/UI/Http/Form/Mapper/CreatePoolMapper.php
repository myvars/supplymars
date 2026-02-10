<?php

namespace App\Note\UI\Http\Form\Mapper;

use App\Note\Application\Command\Pool\CreatePool;
use App\Note\UI\Http\Form\Model\PoolForm;

final class CreatePoolMapper
{
    public function __invoke(PoolForm $data): CreatePool
    {
        return new CreatePool(
            $data->name,
            $data->description,
            $data->isActive,
            $data->isCustomerVisible,
        );
    }
}
