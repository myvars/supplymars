<?php

declare(strict_types=1);

namespace App\Note\Application\Command\Ticket;

use App\Note\Domain\Model\Message\AuthorType;
use App\Note\Domain\Model\Message\MessageVisibility;
use App\Note\Domain\Model\Ticket\TicketPublicId;

final readonly class ReplyToTicket
{
    public function __construct(
        public TicketPublicId $ticketId,
        public int $authorId,
        public AuthorType $authorType,
        public string $body,
        public MessageVisibility $visibility,
    ) {
    }
}
