<?php

declare(strict_types=1);

namespace App\Note\Application\Command\Ticket;

use App\Note\Domain\Model\Message\MessagePublicId;
use App\Note\Domain\Model\Ticket\TicketPublicId;

final readonly class DeleteMessage
{
    public string $id;

    public function __construct(
        public TicketPublicId $ticketId,
        public MessagePublicId $messageId,
    ) {
        $this->id = $this->messageId->value();
    }
}
