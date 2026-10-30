<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Doctrine;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Domain\Model\StatusChange\StatusChangeLogId;
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

    public function deleteFinishedBefore(\DateTimeImmutable $before, int $batchSize): int
    {
        $finalOrderStatuses = array_column(array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => $status->isFinal()), 'value');
        $finalPurchaseOrderStatuses = array_column(array_filter(PurchaseOrderStatus::cases(), fn (PurchaseOrderStatus $status): bool => $status->isFinal()), 'value');

        $entities = [
            [DomainEventType::ORDER_STATUS_CHANGED, CustomerOrder::class, $finalOrderStatuses],
            [DomainEventType::ORDER_ITEM_STATUS_CHANGED, CustomerOrderItem::class, $finalOrderStatuses],
            [DomainEventType::PURCHASE_ORDER_STATUS_CHANGED, PurchaseOrder::class, $finalPurchaseOrderStatuses],
            [DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, PurchaseOrderItem::class, $finalPurchaseOrderStatuses],
        ];

        // Rows are written in time order, so the rows before $before all have lower ids than the first row after it.
        // The search walks that old end of the table once, a window of $batchSize rows at a time. A narrow id range
        // keeps MySQL reading the primary key; given the whole range, it drives some joins from the entity table.
        $endId = $this->firstIdFrom($before) ?? \PHP_INT_MAX;
        $fromId = 0;

        $deleted = 0;
        while ($fromId < $endId) {
            $toId = min($this->idAfter($fromId, $batchSize) ?? $endId, $endId);

            $ids = [];
            foreach ($entities as [$eventType, $entityClass, $finalStatuses]) {
                array_push($ids, ...$this->createQueryBuilder('sc')
                    ->select('sc.id')
                    ->join($entityClass, 'e', 'ON', 'e.id = sc.eventTypeId')
                    ->where('sc.id >= :fromId')
                    ->andWhere('sc.id < :toId')
                    ->andWhere('sc.eventType = :eventType')
                    ->andWhere('sc.eventTimestamp < :before')
                    ->andWhere('e.status IN (:finalStatuses)')
                    ->setParameter('fromId', $fromId)
                    ->setParameter('toId', $toId)
                    ->setParameter('eventType', $eventType)
                    ->setParameter('before', $before)
                    ->setParameter('finalStatuses', $finalStatuses)
                    ->getQuery()
                    ->getSingleColumnResult());
            }

            if ($ids !== []) {
                // Each window is its own short transaction, so no lock is held for long beside the simulation
                $deleted += $this->createQueryBuilder('sc')
                    ->delete()
                    ->where('sc.id IN (:ids)')
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->execute();
            }

            $fromId = $toId;
        }

        return $deleted;
    }

    /** The id $count rows after $fromId, so that ids from $fromId up to it hold $count rows. */
    private function idAfter(int $fromId, int $count): ?int
    {
        $row = $this->createQueryBuilder('sc')
            ->select('sc.id')
            ->where('sc.id >= :fromId')
            ->setParameter('fromId', $fromId)
            ->orderBy('sc.id', 'ASC')
            ->setFirstResult($count)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row === null ? null : (int) $row['id'];
    }

    private function firstIdFrom(\DateTimeImmutable $from): ?int
    {
        // Reads the primary key in order and stops at the first match
        $row = $this->createQueryBuilder('sc')
            ->select('sc.id')
            ->where('sc.eventTimestamp >= :from')
            ->setParameter('from', $from)
            ->orderBy('sc.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row === null ? null : (int) $row['id'];
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
