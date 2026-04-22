<?php

declare(strict_types=1);

namespace App\Note\Application\Handler\Ticket;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use App\Note\Application\Command\Ticket\CreateTicket;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Model\Pool\PoolId;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Repository\PoolRepository;
use App\Note\Domain\Repository\TicketRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateTicketHandler
{
    private const string ROUTE = 'app_note_ticket_show';

    public function __construct(
        private TicketRepository $tickets,
        private PoolRepository $pools,
        private UserRepository $users,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateTicket $command): Result
    {
        $pool = $this->pools->get(PoolId::fromInt($command->poolId));
        if (!$pool instanceof Pool) {
            return Result::fail('Pool not found.');
        }

        $customer = $this->users->get(UserId::fromInt($command->customerId));
        if (!$customer instanceof User) {
            return Result::fail('Customer not found.');
        }

        $ticket = Ticket::create(
            pool: $pool,
            customer: $customer,
            subject: $command->subject,
            body: $command->body,
        );

        $errors = $this->validator->validate($ticket);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->tickets->add($ticket);
        $this->flusher->flush();

        return Result::ok(
            message: 'Ticket created',
            payload: $ticket->getPublicId(),
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: ['id' => $ticket->getPublicId()->value()],
            ),
        );
    }
}
