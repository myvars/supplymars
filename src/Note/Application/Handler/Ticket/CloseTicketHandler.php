<?php

declare(strict_types=1);

namespace App\Note\Application\Handler\Ticket;

use App\Note\Application\Command\Ticket\CloseTicket;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Repository\TicketRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Infrastructure\Security\CurrentUserProvider;

final readonly class CloseTicketHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private FlusherInterface $flusher,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(CloseTicket $command): Result
    {
        $ticket = $this->tickets->getByPublicId($command->id);
        if (!$ticket instanceof Ticket) {
            return Result::fail('Ticket not found.');
        }

        try {
            $ticket->close();
        } catch (\LogicException $logicException) {
            return Result::fail($logicException->getMessage());
        }

        $staff = $this->userProvider->get();
        $ticket->addMessage(Message::system($ticket, 'Ticket closed by ' . $staff->getFullName()));

        $this->flusher->flush();

        return Result::ok('Ticket closed');
    }
}
