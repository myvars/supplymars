<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Domain\Model\ProductImage\productImageId;
use App\Catalog\Domain\Model\ProductImage\ProductImagePublicId;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductImageDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProductImageDoctrineRepository::class)]
interface ProductImageRepository
{
    public function add(ProductImage $productImage): void;

    public function remove(ProductImage $productImage): void;

    public function get(productImageId $id): ?ProductImage;

    public function getByPublicId(ProductImagePublicId $publicId): ?ProductImage;

    public function getNextPositionForProduct(Product $product): int;
}
