<?php

namespace App\Audit\Infrastructure\Persistence\Doctrine;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Domain\Model\StatusChange\StatusChangeLogId;
use App\Audit\Domain\Model\StatusChange\StatusChangeLogPublicId;
use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Shared\Domain\Event\DomainEventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StatusChangeLog>
 */
class StatusChangeLogDoctrineRepository extends ServiceEntityRepository implements StatusChangeLogRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatusChangeLog::class);
    }

    public function add(StatusChangeLog $statusChangeLog): void
    {
        $this->getEntityManager()->persist($statusChangeLog);
    }

    public function remove(StatusChangeLog $statusChangeLog): void
    {
        $this->getEntityManager()->remove($statusChangeLog);
    }

    public function get(StatusChangeLogId $id): ?StatusChangeLog
    {
        return $this->find($id);
    }

    public function getByPublicId(StatusChangeLogPublicId $publicId): ?StatusChangeLog
    {
        return $this->findOneBy(['publicId' => $publicId]);
    }

    public function findByEvent(DomainEventType $eventType, int $eventTypeId): array
    {
        return $this->findBy(
            ['eventType' => $eventType, 'eventTypeId' => $eventTypeId],
            ['eventTimestamp' => 'ASC']
        );
    }

    public function findPoStatusChangeByStatus(int $poItemId, PurchaseOrderStatus $status): ?StatusChangeLog
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.eventTypeId = :poItemId')
            ->andWhere('sc.eventType = :domainEventType')
            ->andWhere('sc.status = :status')
            ->setParameter('poItemId', $poItemId)
            ->setParameter('domainEventType', DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED)
            ->setParameter('status', $status->value)
            ->orderBy('sc.eventTimestamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findStatusChanges(DomainEventType $eventType, int $id): array
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.eventTypeId = :id')
            ->andWhere('sc.eventType = :domainEventType')
            ->setParameter('id', $id)
            ->setParameter('domainEventType', $eventType->value)
            ->orderBy('sc.eventTimestamp', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
