<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Doctrine;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Domain\Model\StatusChange\StatusChangeLogId;
use App\Audit\Domain\Model\StatusChange\StatusChangeLogPublicId;
use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
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

    public function deleteFinishedBefore(\DateTimeImmutable $before, int $limit): int
    {
        $finalOrderStatuses = array_column(array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => $status->isFinal()), 'value');
        $finalPurchaseOrderStatuses = array_column(array_filter(PurchaseOrderStatus::cases(), fn (PurchaseOrderStatus $status): bool => $status->isFinal()), 'value');

        $entities = [
            [DomainEventType::ORDER_STATUS_CHANGED, CustomerOrder::class, $finalOrderStatuses],
            [DomainEventType::ORDER_ITEM_STATUS_CHANGED, CustomerOrderItem::class, $finalOrderStatuses],
            [DomainEventType::PURCHASE_ORDER_STATUS_CHANGED, PurchaseOrder::class, $finalPurchaseOrderStatuses],
            [DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, PurchaseOrderItem::class, $finalPurchaseOrderStatuses],
        ];

        // Rows are written in time order, so every row before $before has a lower id than the first row after it.
        // Bounding the search by that id keeps it to the old end of the table rather than scanning all of it.
        $boundaryId = $this->createQueryBuilder('sc')
            ->select('sc.id')
            ->where('sc.eventTimestamp >= :before')
            ->setParameter('before', $before)
            ->orderBy('sc.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()['id'] ?? null;

        $ids = [];
        foreach ($entities as [$eventType, $entityClass, $finalStatuses]) {
            $remaining = $limit - count($ids);
            if ($remaining === 0) {
                break;
            }

            $qb = $this->createQueryBuilder('sc')
                ->select('sc.id')
                ->join($entityClass, 'e', 'WITH', 'e.id = sc.eventTypeId')
                ->where('sc.eventType = :eventType')
                ->andWhere('sc.eventTimestamp < :before')
                ->andWhere('e.status IN (:finalStatuses)')
                ->setParameter('eventType', $eventType)
                ->setParameter('before', $before)
                ->setParameter('finalStatuses', $finalStatuses)
                ->setMaxResults($remaining);

            if ($boundaryId !== null) {
                $qb->andWhere('sc.id < :boundaryId')->setParameter('boundaryId', $boundaryId);
            }

            array_push($ids, ...$qb->getQuery()->getSingleColumnResult());
        }

        if ($ids === []) {
            return 0;
        }

        return $this->createQueryBuilder('sc')
            ->delete()
            ->where('sc.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    /**
     * @return array<int, StatusChangeLog>
     */
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
