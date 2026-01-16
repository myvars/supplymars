<?php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Subcategory\CreateSubcategory;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\UI\Http\Form\Model\SubcategoryForm;

final class CreateSubcategoryMapper
{
    public function __invoke(SubcategoryForm $data): CreateSubcategory
    {
        return new CreateSubcategory(
            $data->name,
            CategoryId::fromInt($data->categoryId),
            $data->defaultMarkup,
            $data->priceModel,
            $data->ownerId,
            $data->isActive
        );
    }
}
