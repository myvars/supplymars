<?php

namespace App\Note\Application\Handler\Ticket;

use App\Note\Application\Command\Ticket\ToggleSnoozeTicket;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Repository\TicketRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Infrastructure\Security\CurrentUserProvider;

final readonly class ToggleSnoozeTicketHandler
{
    private const string ROUTE = 'app_note_ticket_show';

    public function __construct(
        private TicketRepository $tickets,
        private FlusherInterface $flusher,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(ToggleSnoozeTicket $command): Result
    {
        $ticket = $this->tickets->getByPublicId($command->ticketId);
        if (!$ticket instanceof Ticket) {
            return Result::fail('Ticket not found.');
        }

        $staff = $this->userProvider->get();

        if ($ticket->isSnoozed()) {
            $ticket->unsnooze();
            $ticket->addMessage(Message::system($ticket, 'Ticket unsnoozed by ' . $staff->getFullName()));
            $message = 'Ticket unsnoozed';
        } else {
            $ticket->snooze(new \DateTimeImmutable('+24 hours'));
            $ticket->addMessage(Message::system($ticket, 'Ticket snoozed by ' . $staff->getFullName()));
            $message = 'Ticket snoozed';
        }

        $this->flusher->flush();

        return Result::ok(
            message: $message,
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: ['id' => $ticket->getPublicId()->value()],
            ),
        );
    }
}
