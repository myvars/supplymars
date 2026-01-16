<?php

namespace App\Purchasing\Domain\Repository;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategoryPublicId;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierSubcategoryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SupplierSubcategoryDoctrineRepository::class)]
interface SupplierSubcategoryRepository
{
    public function add(SupplierSubcategory $supplierSubcategory): void;

    public function remove(SupplierSubcategory $supplierSubcategory): void;

    public function get(SupplierSubcategoryId $id): ?SupplierSubcategory;

    public function getByPublicId(SupplierSubcategoryPublicId $publicId): ?SupplierSubcategory;
}
