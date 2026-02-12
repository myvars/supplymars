<?php

namespace App\Audit\Infrastructure\Persistence\Doctrine;

use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use App\Audit\Domain\Model\StockChange\SupplierStockChangeLogId;
use App\Audit\Domain\Model\StockChange\SupplierStockChangeLogPublicId;
use App\Audit\Domain\Repository\SupplierStockChangeLogRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupplierStockChangeLog>
 */
class SupplierStockChangeLogDoctrineRepository extends ServiceEntityRepository implements SupplierStockChangeLogRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierStockChangeLog::class);
    }

    public function add(SupplierStockChangeLog $supplierStockChangeLog): void
    {
        $this->getEntityManager()->persist($supplierStockChangeLog);
    }

    public function remove(SupplierStockChangeLog $supplierStockChangeLog): void
    {
        $this->getEntityManager()->remove($supplierStockChangeLog);
    }

    public function get(SupplierStockChangeLogId $id): ?SupplierStockChangeLog
    {
        return $this->find($id);
    }

    public function getByPublicId(SupplierStockChangeLogPublicId $publicId): ?SupplierStockChangeLog
    {
        return $this->findOneBy(['publicId' => $publicId]);
    }

    public function findBySupplierProductIds(array $supplierProductIds, ?\DateTimeImmutable $since = null): array
    {
        if ($supplierProductIds === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('l')
            ->where('l.supplierProductId IN (:ids)')
            ->setParameter('ids', $supplierProductIds)
            ->orderBy('l.eventTimestamp', 'ASC');

        if ($since instanceof \DateTimeImmutable) {
            $qb->andWhere('l.eventTimestamp >= :since')
                ->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }
}
