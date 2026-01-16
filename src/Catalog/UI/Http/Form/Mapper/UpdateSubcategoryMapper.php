<?php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Subcategory\UpdateSubcategory;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Catalog\UI\Http\Form\Model\SubcategoryForm;

final class UpdateSubcategoryMapper
{
    public function __invoke(SubcategoryForm $data): UpdateSubcategory
    {
        return new UpdateSubcategory(
            SubcategoryPublicId::fromString($data->id),
            $data->name,
            CategoryId::fromInt($data->categoryId),
            $data->defaultMarkup,
            $data->priceModel,
            $data->ownerId,
            $data->isActive
        );
    }
}
