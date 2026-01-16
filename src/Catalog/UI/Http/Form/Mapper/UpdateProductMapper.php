<?php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Product\UpdateProduct;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerId;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryId;
use App\Catalog\UI\Http\Form\Model\ProductForm;

final class UpdateProductMapper
{
    public function __invoke(ProductForm $data): UpdateProduct
    {
        return new UpdateProduct(
            ProductPublicId::fromString($data->id),
            $data->name,
            $data->description,
            CategoryId::fromInt($data->categoryId),
            SubcategoryId::fromInt($data->subcategoryId),
            ManufacturerId::fromInt($data->manufacturerId),
            $data->mfrPartNumber,
            $data->ownerId,
            $data->isActive
        );
    }
}
