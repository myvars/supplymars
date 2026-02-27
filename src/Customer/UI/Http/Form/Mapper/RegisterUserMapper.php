<?php

namespace App\Customer\UI\Http\Form\Mapper;

use App\Customer\Application\Command\RegisterUser;
use App\Customer\UI\Http\Form\Model\RegistrationForm;

final class RegisterUserMapper
{
    public function __invoke(RegistrationForm $data): RegisterUser
    {
        return new RegisterUser(
            $data->fullName,
            $data->email,
            $data->plainPassword,
        );
    }
}
