<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

final readonly class RequestPasswordReset
{
    public function __construct(
        public string $email,
    ) {
    }
}
