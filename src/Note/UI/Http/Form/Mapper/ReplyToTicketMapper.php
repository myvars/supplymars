<?php

namespace App\Note\UI\Http\Form\Mapper;

use App\Note\Application\Command\Ticket\ReplyToTicket;
use App\Note\Domain\Model\Message\AuthorType;
use App\Note\Domain\Model\Message\MessageVisibility;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\UI\Http\Form\Model\ReplyForm;

final readonly class ReplyToTicketMapper
{
    public function __construct(
        private Ticket $ticket,
        private int $authorId,
    ) {
    }

    public function __invoke(ReplyForm $data): ReplyToTicket
    {
        return new ReplyToTicket(
            ticketId: $this->ticket->getPublicId(),
            authorId: $this->authorId,
            authorType: AuthorType::STAFF,
            body: $data->body,
            visibility: MessageVisibility::from($data->visibility),
        );
    }
}
