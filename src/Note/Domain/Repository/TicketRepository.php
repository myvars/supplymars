<?php

namespace App\Note\Domain\Repository;

use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Model\Ticket\TicketId;
use App\Note\Domain\Model\Ticket\TicketPublicId;
use App\Note\Infrastructure\Persistence\Doctrine\TicketDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(TicketDoctrineRepository::class)]
interface TicketRepository extends FindByCriteriaInterface
{
    public function add(Ticket $ticket): void;

    public function remove(Ticket $ticket): void;

    public function get(TicketId $id): ?Ticket;

    public function getByPublicId(TicketPublicId $publicId): ?Ticket;

    public function countOpenTicketsForUser(int $userId): int;
}
