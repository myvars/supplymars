<?php

namespace App\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\DeleteProduct;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteProduct $command): Result
    {
        $product = $this->products->getByPublicId($command->id);
        if (!$product instanceof Product) {
            return Result::fail('Product not found.');
        }

        $this->products->remove($product);
        $this->flusher->flush();

        return Result::ok('Product deleted');
    }
}
