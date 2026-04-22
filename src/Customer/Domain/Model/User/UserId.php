<?php

declare(strict_types=1);

namespace App\Customer\Domain\Model\User;

use App\Shared\Domain\ValueObject\AbstractIntId;

final readonly class UserId extends AbstractIntId
{
    // Inherits strict validation and factories.
}
