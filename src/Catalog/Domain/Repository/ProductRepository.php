<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use App\Customer\Domain\Model\User\User;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProductDoctrineRepository::class)]
interface ProductRepository extends FindByCriteriaInterface
{
    public function add(Product $product): void;

    public function remove(Product $product): void;

    public function get(ProductId $id): ?Product;

    public function getByPublicId(ProductPublicId $publicId): ?Product;

    public function getByName(string $name): ?Product;

    /** @return Product[] */
    public function findRandomProducts(int $limit = 10): array;

    public function findByMfrPartNumber(string $mfrPartNumber): ?Product;

    public function existsForOwner(User $owner): bool;

    /** @return iterable<Product> streamed by id, for batch jobs that clear the entity manager */
    public function iterateAll(): iterable;

    public function count(): int;
}
