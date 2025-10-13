<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Catalog\Infrastructure\Persistence\Doctrine\CategoryDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CategoryDoctrineRepository::class)]
interface CategoryRepository extends FindByCriteriaInterface
{
    public function add(Category $category): void;
    public function remove(Category $category): void;
    public function get(CategoryId $id): ?Category;
    public function getByPublicId(CategoryPublicId $publicId): ?Category;
}
