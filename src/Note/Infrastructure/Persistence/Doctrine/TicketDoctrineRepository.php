<?php

namespace App\Note\Infrastructure\Persistence\Doctrine;

use App\Note\Application\Search\TicketSearchCriteria;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Model\Ticket\TicketId;
use App\Note\Domain\Model\Ticket\TicketPublicId;
use App\Note\Domain\Repository\TicketRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<Ticket>
 *
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class TicketDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, TicketRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CurrentUserProvider $userProvider,
    ) {
        parent::__construct($registry, Ticket::class);
    }

    public function add(Ticket $ticket): void
    {
        $this->getEntityManager()->persist($ticket);
    }

    public function remove(Ticket $ticket): void
    {
        $this->getEntityManager()->remove($ticket);
    }

    public function get(TicketId $id): ?Ticket
    {
        return $this->find($id->value());
    }

    public function getByPublicId(TicketPublicId $publicId): ?Ticket
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    /**
     * @return AdapterInterface<Ticket>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        if (!$criteria instanceof TicketSearchCriteria) {
            throw new \InvalidArgumentException('Expected TicketSearchCriteria');
        }

        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.pool', 'p')
            ->addSelect('p')
            ->leftJoin('t.customer', 'c')
            ->addSelect('c');

        if ($criteria->getQuery()) {
            $qb->andWhere('t.subject LIKE :query OR c.fullName LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        if ($criteria->poolId !== null) {
            $qb->andWhere('t.pool = :poolId')
                ->setParameter('poolId', $criteria->poolId);
        }

        if ($criteria->status !== null) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $criteria->status);
        }

        if (!$criteria->showSnoozed) {
            $qb->andWhere('t.snoozedUntil IS NULL OR t.snoozedUntil <= :now')
                ->setParameter('now', new \DateTimeImmutable());
        }

        if ($criteria->myPools && $this->userProvider->hasUser()) {
            $qb->innerJoin('p.subscribers', 'sub', 'WITH', 'sub = :currentUser')
                ->setParameter('currentUser', $this->userProvider->get());
        }

        $qb->orderBy('t.' . $sort, $sortDirection);

        return new QueryAdapter($qb);
    }
}
