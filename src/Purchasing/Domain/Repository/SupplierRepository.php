<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Repository;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\Supplier\SupplierPublicId;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SupplierDoctrineRepository::class)]
interface SupplierRepository extends FindByCriteriaInterface
{
    public function add(Supplier $supplier): void;

    public function remove(Supplier $supplier): void;

    public function get(SupplierId $id): ?Supplier;

    public function getByPublicId(SupplierPublicId $publicId): ?Supplier;

    public function getRandomSupplier(): ?Supplier;

    public function getWarehouseSupplier(): ?Supplier;

    /** @return Supplier[] */
    public function findNonWarehouseSupplier(): array;
}
