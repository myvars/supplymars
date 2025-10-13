<?php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Product\CreateProduct;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryId;
use App\Catalog\UI\Http\Form\Model\ProductForm;

final class CreateProductMapper
{
    public function __invoke(ProductForm $data): CreateProduct
    {
        return new CreateProduct(
            $data->name,
            $data->description,
            CategoryId::fromInt($data->categoryId),
            SubcategoryId::fromInt($data->subcategoryId),
            ManufacturerId::fromInt($data->manufacturerId),
            $data->mfrPartNumber,
            $data->ownerId,
            $data->isActive,
        );
    }
}
