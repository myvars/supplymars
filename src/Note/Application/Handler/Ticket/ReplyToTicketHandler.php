<?php

declare(strict_types=1);

namespace App\Note\Application\Handler\Ticket;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use App\Note\Application\Command\Ticket\ReplyToTicket;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Repository\TicketRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class ReplyToTicketHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private UserRepository $users,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(ReplyToTicket $command): Result
    {
        $ticket = $this->tickets->getByPublicId($command->ticketId);
        if (!$ticket instanceof Ticket) {
            return Result::fail('Ticket not found.');
        }

        $author = $this->users->get(UserId::fromInt($command->authorId));
        if (!$author instanceof User) {
            return Result::fail('Author not found.');
        }

        $message = Message::create(
            ticket: $ticket,
            author: $author,
            authorType: $command->authorType,
            body: $command->body,
            visibility: $command->visibility,
        );

        $ticket->addMessage($message);
        $ticket->transitionStatusForReply($command->authorType);

        $this->flusher->flush();

        return Result::ok(message: 'Reply sent');
    }
}
