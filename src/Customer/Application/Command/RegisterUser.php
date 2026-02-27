<?php

namespace App\Customer\Application\Command;

final readonly class RegisterUser
{
    public function __construct(
        public string $fullName,
        public string $email,
        public string $plainPassword,
    ) {
    }
}
