<?php

namespace App\Audit\Domain\Repository;

use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use App\Audit\Domain\Model\StockChange\SupplierStockChangeLogId;
use App\Audit\Domain\Model\StockChange\SupplierStockChangeLogPublicId;
use App\Audit\Infrastructure\Persistence\Doctrine\SupplierStockChangeLogDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SupplierStockChangeLogDoctrineRepository::class)]
interface SupplierStockChangeLogRepository
{
    public function add(SupplierStockChangeLog $supplierStockChangeLog): void;

    public function remove(SupplierStockChangeLog $supplierStockChangeLog): void;

    public function get(SupplierStockChangeLogId $id): ?SupplierStockChangeLog;

    public function getByPublicId(SupplierStockChangeLogPublicId $publicId): ?SupplierStockChangeLog;

    /**
     * @param array<int, int> $supplierProductIds
     *
     * @return array<int, SupplierStockChangeLog>
     */
    public function findBySupplierProductIds(array $supplierProductIds, ?\DateTimeImmutable $since = null): array;
}
