<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Repository;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierProductDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SupplierProductDoctrineRepository::class)]
interface SupplierProductRepository extends FindByCriteriaInterface
{
    public function add(SupplierProduct $supplierProduct): void;

    public function remove(SupplierProduct $supplierProduct): void;

    public function get(SupplierProductId $id): ?SupplierProduct;

    public function getByPublicId(SupplierProductPublicId $publicId): ?SupplierProduct;

    /** @return SupplierProduct[] */
    public function findRandomSupplierProducts(Supplier $supplier, int $itemCount): array;

    /** @return SupplierProduct[] */
    public function findBySupplier(Supplier $supplier): array;

    /** @return SupplierProduct[] */
    public function findInactive(int $limit): array;
}
