<?php

declare(strict_types=1);

namespace App\Note\Domain\Model\Message;

use App\Shared\Domain\ValueObject\AbstractUlidId;

final readonly class MessagePublicId extends AbstractUlidId
{
    // Inherits strict validation and factories.
}
