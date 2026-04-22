<?php

declare(strict_types=1);

namespace App\Customer\Domain\Model\Address;

use App\Shared\Domain\ValueObject\AbstractIntId;

final readonly class AddressId extends AbstractIntId
{
    // Inherits strict validation and factories.
}
