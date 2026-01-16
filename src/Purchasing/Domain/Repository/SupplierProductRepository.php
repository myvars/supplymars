<?php

namespace App\Purchasing\Domain\Repository;

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
}
