<?php

declare(strict_types=1);

namespace App\Catalog\Application\Handler\ProductImage;

use App\Catalog\Application\Command\ProductImage\ReorderProductImage;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class ReorderProductImageHandler
{
    public function __construct(
        private ProductRepository $products,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(ReorderProductImage $command): Result
    {
        if ($command->newImageOrder === []) {
            return Result::fail('No image ordering found.');
        }

        $product = $this->products->getByPublicId($command->id);
        if (!$product instanceof Product) {
            return Result::fail('Product not found.');
        }

        $product->reorderProductImagesBy($command->newImageOrder);

        $this->flusher->flush();

        return Result::ok();
    }
}
