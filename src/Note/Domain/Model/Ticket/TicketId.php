<?php

declare(strict_types=1);

namespace App\Note\Domain\Model\Ticket;

use App\Shared\Domain\ValueObject\AbstractIntId;

final readonly class TicketId extends AbstractIntId
{
    // Inherits strict validation and factories.
}
