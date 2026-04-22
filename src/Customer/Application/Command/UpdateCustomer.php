<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Model\User\UserPublicId;

final readonly class UpdateCustomer
{
    public function __construct(
        public UserPublicId $id,
        public string $fullName,
        public string $email,
        public bool $isVerified,
        public bool $isStaff,
    ) {
    }
}
