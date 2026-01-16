<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Model\Subcategory\SubcategoryId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Catalog\Infrastructure\Persistence\Doctrine\SubcategoryDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SubcategoryDoctrineRepository::class)]
interface SubcategoryRepository extends FindByCriteriaInterface
{
    public function add(Subcategory $subcategory): void;

    public function remove(Subcategory $subcategory): void;

    public function get(SubcategoryId $id): ?Subcategory;

    public function getByPublicId(SubcategoryPublicId $publicId): ?Subcategory;
}
