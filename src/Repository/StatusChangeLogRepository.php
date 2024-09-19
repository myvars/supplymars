<?php

namespace App\Repository;

use App\Entity\StatusChangeLog;
use App\Enum\DomainEventType;
use App\Enum\PurchaseOrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StatusChangeLog>
 */
class StatusChangeLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatusChangeLog::class);
    }

    //    /**
    //     * @return StatusChangeLog[] Returns an array of StatusChangeLog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?StatusChangeLog
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findPoStatusChangeByStatus(int $poItemId, PurchaseOrderStatus $status): ?StatusChangeLog
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.eventTypeId = :poItemId')
            ->andWhere('sc.eventType = :domainEventType')
            ->andWhere('sc.status = :status')
            ->setParameter('poItemId', $poItemId)
            ->setParameter('domainEventType', DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED)
            ->setParameter('status', $status->value)
            ->orderBy('sc.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
