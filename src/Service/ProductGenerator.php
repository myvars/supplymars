<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Service\ProductMapping\CategoryMapper;
use App\Service\ProductMapping\ManufacturerMapper;
use App\Service\ProductMapping\ProductMapper;
use App\Service\ProductMapping\SubcategoryMapper;

class ProductGenerator
{
    public function __construct(
        private readonly CategoryMapper $categoryMapper,
        private readonly SubcategoryMapper $subcategoryMapper,
        private readonly ManufacturerMapper $manufacturerMapper,
        private readonly ProductMapper $productMapper,
        private readonly ProductPriceCalculator $productPriceCalculator
    ) {
    }

    public function createFromSupplierProduct(SupplierProduct $supplierProduct): Product
    {
        if ($supplierProduct->getProduct()) {
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