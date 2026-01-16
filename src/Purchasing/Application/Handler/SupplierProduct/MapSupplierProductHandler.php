<?php

namespace App\Purchasing\Application\Handler\SupplierProduct;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Purchasing\Application\Command\SupplierProduct\MapSupplierProduct;
use App\Purchasing\Application\Service\SupplierProductMappingService;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class MapSupplierProductHandler
{
    public function __construct(
        private SupplierProductRepository $supplierProducts,
        private FlusherInterface $flusher,
        private SupplierProductMappingService $productMapper,
    ) {
    }

    public function __invoke(MapSupplierProduct $command): Result
    {
        $supplierProduct = $this->supplierProducts->getByPublicId($command->id);
        if (!$supplierProduct instanceof SupplierProduct) {
            return Result::fail('Supplier product not found.');
        }

        if ($supplierProduct->getProduct() instanceof Product) {
            return Result::fail('Supplier product already mapped.');
        }

        try {
            $product = $this->productMapper->map($supplierProduct);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return Result::fail('Mapping failed: ' . $invalidArgumentException->getMessage());
        }

        $this->flusher->flush();

        return Result::ok('Supplier product mapped.', ProductId::fromInt($product->getId()));
    }
}
