<?php

declare(strict_types=1);

namespace App\Catalog\Application\Handler\ProductImage;

use App\Catalog\Application\Command\ProductImage\DeleteProductImage;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Domain\Repository\ProductImageRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteProductImageHandler
{
    public function __construct(
        private ProductImageRepository $productImages,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteProductImage $command): Result
    {
        $productImage = $this->productImages->getByPublicId($command->id);
        if (!$productImage instanceof ProductImage) {
            return Result::fail('Product image not found.');
        }

        $productImage->delete();
        $productImage->getProduct()->removeProductImage($productImage);

        $this->productImages->remove($productImage);

        $this->flusher->flush();

        return Result::ok('Product image deleted');
    }
}
