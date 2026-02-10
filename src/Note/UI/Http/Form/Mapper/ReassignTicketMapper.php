<?php

namespace App\Note\UI\Http\Form\Mapper;

use App\Note\Application\Command\Ticket\ReassignTicket;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\UI\Http\Form\Model\ReassignForm;

final readonly class ReassignTicketMapper
{
    public function __construct(
        private Ticket $ticket,
    ) {
    }

    public function __invoke(ReassignForm $data): ReassignTicket
    {
        return new ReassignTicket(
            ticketId: $this->ticket->getPublicId(),
            newPoolId: $data->poolId,
        );
    }
}
