<?php

namespace App\Purchasing\Application\Handler\SupplierProduct;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Application\Command\SupplierProduct\RemoveSupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;

final readonly class RemoveSupplierProductHandler
{
    public function __construct(
        private SupplierProductRepository $supplierProducts,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(RemoveSupplierProduct $command): Result
    {
        $supplierProduct = $this->supplierProducts->getByPublicId($command->id);
        if (!$supplierProduct instanceof SupplierProduct) {
            return Result::fail('Supplier product not found.');
        }

        $product = $supplierProduct->getProduct();
        if (!$product instanceof Product) {
            return Result::fail('Supplier product not mapped');
        }

        $product->removeSupplierProduct($this->markupCalculator, $supplierProduct);

        $this->flusher->flush();

        return Result::ok('Supplier product removed');
    }
}
