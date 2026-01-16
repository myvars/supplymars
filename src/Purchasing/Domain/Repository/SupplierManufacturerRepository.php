<?php

namespace App\Purchasing\Domain\Repository;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturerId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturerPublicId;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierManufacturerDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SupplierManufacturerDoctrineRepository::class)]
interface SupplierManufacturerRepository
{
    public function add(SupplierManufacturer $supplierManufacturer): void;

    public function remove(SupplierManufacturer $supplierManufacturer): void;

    public function get(SupplierManufacturerId $id): ?SupplierManufacturer;

    public function getByPublicId(SupplierManufacturerPublicId $publicId): ?SupplierManufacturer;
}
