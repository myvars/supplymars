<?php

declare(strict_types=1);

namespace App\Note\Application\Command\Ticket;

use App\Note\Domain\Model\Ticket\TicketPublicId;

final readonly class ReassignTicket
{
    public function __construct(
        public TicketPublicId $ticketId,
        public int $newPoolId,
    ) {
    }
}
