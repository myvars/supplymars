<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerId;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;
use App\Catalog\Infrastructure\Persistence\Doctrine\ManufacturerDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ManufacturerDoctrineRepository::class)]
interface ManufacturerRepository extends FindByCriteriaInterface
{
    public function add(Manufacturer $manufacturer): void;

    public function remove(Manufacturer $manufacturer): void;

    public function get(ManufacturerId $id): ?Manufacturer;

    public function getByPublicId(ManufacturerPublicId $publicId): ?Manufacturer;
}
