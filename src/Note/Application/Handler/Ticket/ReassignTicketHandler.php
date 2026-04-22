<?php

declare(strict_types=1);

namespace App\Note\Application\Handler\Ticket;

use App\Note\Application\Command\Ticket\ReassignTicket;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Model\Pool\PoolId;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Repository\PoolRepository;
use App\Note\Domain\Repository\TicketRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Infrastructure\Security\CurrentUserProvider;

final readonly class ReassignTicketHandler
{
    private const string ROUTE = 'app_note_ticket_show';

    public function __construct(
        private TicketRepository $tickets,
        private PoolRepository $pools,
        private FlusherInterface $flusher,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(ReassignTicket $command): Result
    {
        $ticket = $this->tickets->getByPublicId($command->ticketId);
        if (!$ticket instanceof Ticket) {
            return Result::fail('Ticket not found.');
        }

        $newPool = $this->pools->get(PoolId::fromInt($command->newPoolId));
        if (!$newPool instanceof Pool) {
            return Result::fail('Pool not found.');
        }

        $oldPoolName = $ticket->getPool()->getName();

        if ($ticket->getPool()->getId() === $newPool->getId()) {
            return Result::fail('Ticket is already in this pool.');
        }

        $ticket->reassignPool($newPool);

        $staff = $this->userProvider->get();
        $ticket->addMessage(Message::system(
            $ticket,
            'Ticket reassigned from ' . $oldPoolName . ' to ' . $newPool->getName() . ' by ' . $staff->getFullName(),
        ));

        $this->flusher->flush();

        return Result::ok(
            message: 'Ticket reassigned to ' . $newPool->getName(),
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: ['id' => $ticket->getPublicId()->value()],
            ),
        );
    }
}
