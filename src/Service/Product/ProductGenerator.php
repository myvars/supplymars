<?php

namespace App\Service\Product;

use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;

final class ProductGenerator implements CrudActionInterface
{
    public function __construct(
        private readonly CategoryMapper $categoryMapper,
        private readonly SubcategoryMapper $subcategoryMapper,
        private readonly ManufacturerMapper $manufacturerMapper,
        private readonly ProductMapper $productMapper,
        private readonly ProductPriceCalculator $productPriceCalculator
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $supplierProduct = $crudOptions->getEntity();
        if (!$supplierProduct instanceof SupplierProduct) {
            throw new \InvalidArgumentException('Entity must be an instance of SupplierProduct');
        }

        $this->createFromSupplierProduct($supplierProduct);
    }

    public function createFromSupplierProduct(SupplierProduct $supplierProduct): Product
    {
        if ($supplierProduct->getProduct() instanceof Product) {
            throw new \InvalidArgumentException('Product already exists');
        }

        $manufacturer = $this->manufacturerMapper->createManufacturerFromSupplierProduct($supplierProduct);
        $category = $this->categoryMapper->createCategoryFromSupplierProduct($supplierProduct);
        $subcategory = $this->subcategoryMapper->createSubcategoryFromSupplierProduct(
            $supplierProduct,
            $category
        );

        $product = $this->productMapper->createProductFromSupplierProduct(
            $supplierProduct,
            $manufacturer,
            $subcategory
        );

        $this->productPriceCalculator->recalculatePrice($product,true);

        return $product;
    }
}