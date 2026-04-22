<?php

declare(strict_types=1);

namespace App\Customer\Domain\Model\User;

use App\Shared\Domain\ValueObject\AbstractUlidId;

final readonly class ResetPasswordRequestPublicId extends AbstractUlidId
{
    // Inherits strict validation and factories.
}
