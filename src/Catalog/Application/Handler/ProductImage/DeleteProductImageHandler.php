<?php

declare(strict_types=1);

namespace App\Catalog\Application\Handler\ProductImage;

use App\Catalog\Application\Command\ProductImage\DeleteProductImage;
use App\Catalog\Domain\Model\ProductImage\Event\ProductImageWasDeletedEvent;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Domain\Repository\ProductImageRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class DeleteProductImageHandler
{
    public function __construct(
        private ProductImageRepository $productImages,
        private FlusherInterface $flusher,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(DeleteProductImage $command): Result
    {
        $productImage = $this->productImages->getByPublicId($command->id);
        if (!$productImage instanceof ProductImage) {
            return Result::fail('Product image not found.');
        }

        $imageName = $productImage->getImageName();
        $product = $productImage->getProduct();

        $product->removeProductImage($productImage);

        $this->productImages->remove($productImage);

        $this->flusher->flush();

        $this->dispatcher->dispatch(new ProductImageWasDeletedEvent($product->getPublicId(), $imageName));

        return Result::ok('Product image deleted');
    }
}
