<?php

namespace App\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\DeleteProduct;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private FlusherInterface $flusher,
        private Security $security,
    ) {
    }

    public function __invoke(DeleteProduct $command): Result
    {
        if (!$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return Result::fail('Deleting is disabled for this user.');
        }

        $product = $this->products->getByPublicId($command->id);
        if (!$product instanceof Product) {
            return Result::fail('Product not found.');
        }

        $this->products->remove($product);
        $this->flusher->flush();

        return Result::ok(message: 'Product deleted');
    }
}
