<?php

namespace App\Service\SupplierProduct;

use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Utility\DomainEventDispatcher;

final readonly class RemoveMappedProduct implements CrudActionInterface
{
    public function __construct(
        private ActiveSourceCalculator $activeSourceCalculator,
        private DomainEventDispatcher $domainEventDispatcher,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $supplierProduct = $crudOptions->getEntity();
        if (!$supplierProduct instanceof SupplierProduct) {
            throw new \InvalidArgumentException('Entity must be an instance of SupplierProduct');
        }

        $this->removeMappedProduct($supplierProduct);
    }

    public function removeMappedProduct(SupplierProduct $supplierProduct): void
    {
        $product = $supplierProduct->getProduct();
        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('Supplier product must be mapped to a product');
        }

        $this->activeSourceCalculator->removeMappedProduct($supplierProduct);
        $this->activeSourceCalculator->recalculateActiveSource($product);

        $this->domainEventDispatcher->dispatchProviderEvents($supplierProduct);
    }
}
