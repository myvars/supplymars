<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProductDoctrineRepository::class)]
interface ProductRepository extends FindByCriteriaInterface
{
    public function add(Product $product): void;

    public function remove(Product $product): void;

    public function get(ProductId $id): ?Product;

    public function getByPublicId(ProductPublicId $publicId): ?Product;

    /** @return Product[] */
    public function findRandomProducts(int $limit = 10): array;

    public function findByMfrPartNumber(string $mfrPartNumber): ?Product;
}
