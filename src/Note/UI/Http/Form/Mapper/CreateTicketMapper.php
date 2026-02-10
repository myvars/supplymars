<?php

namespace App\Note\UI\Http\Form\Mapper;

use App\Note\Application\Command\Ticket\CreateTicket;
use App\Note\UI\Http\Form\Model\TicketForm;

final class CreateTicketMapper
{
    public function __invoke(TicketForm $data): CreateTicket
    {
        return new CreateTicket(
            $data->poolId,
            $data->customerId,
            $data->subject,
            $data->body,
        );
    }
}
