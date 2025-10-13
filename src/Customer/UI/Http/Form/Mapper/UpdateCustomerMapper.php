<?php

namespace App\Customer\UI\Http\Form\Mapper;

use App\Customer\Application\Command\UpdateCustomer;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\UI\Http\Form\Model\CustomerForm;

final class UpdateCustomerMapper
{
    public function __invoke(CustomerForm $data): UpdateCustomer
    {
        return new UpdateCustomer(
            UserPublicId::fromString($data->id),
            $data->fullName,
            $data->email,
            $data->isVerified,
            $data->isStaff
        );
    }
}

