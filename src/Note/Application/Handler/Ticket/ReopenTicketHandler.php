<?php

declare(strict_types=1);

namespace App\Note\Application\Handler\Ticket;

use App\Customer\Infrastructure\Security\CurrentUserProvider;
use App\Note\Application\Command\Ticket\ReopenTicket;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Repository\TicketRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class ReopenTicketHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private FlusherInterface $flusher,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(ReopenTicket $command): Result
    {
        $ticket = $this->tickets->getByPublicId($command->id);
        if (!$ticket instanceof Ticket) {
            return Result::fail('Ticket not found.');
        }

        try {
            $ticket->reopen();
        } catch (\LogicException $logicException) {
            return Result::fail($logicException->getMessage());
        }

        $staff = $this->userProvider->get();
        $ticket->addMessage(Message::system($ticket, 'Ticket reopened by ' . $staff->getFullName()));

        $this->flusher->flush();

        return Result::ok('Ticket reopened');
    }
}
