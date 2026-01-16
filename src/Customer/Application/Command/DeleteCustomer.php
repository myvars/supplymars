<?php

namespace App\Customer\Application\Command;

use App\Customer\Domain\Model\User\UserPublicId;

final readonly class DeleteCustomer
{
    public function __construct(public UserPublicId $id)
    {
    }
}
