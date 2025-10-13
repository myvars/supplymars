<?php

namespace App\Purchasing\Domain\Repository;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryPublicId;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierCategoryDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SupplierCategoryDoctrineRepository::class)]
interface SupplierCategoryRepository
{
    public function add(SupplierCategory $supplierCategory): void;
    public function remove(SupplierCategory $supplierCategory): void;
    public function get(SupplierCategoryId $id): ?SupplierCategory;
    public function getByPublicId(SupplierCategoryPublicId $publicId): ?SupplierCategory;
}
