<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

final readonly class ResetPassword
{
    public function __construct(
        public string $token,
        public string $plainPassword,
    ) {
    }
}
