<?php

namespace App\Note\Application\Command\Ticket;

use App\Note\Domain\Model\Ticket\TicketPublicId;

final readonly class ToggleSnoozeTicket
{
    public function __construct(
        public TicketPublicId $ticketId,
    ) {
    }
}
