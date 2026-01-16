<?php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Category\CreateCategory;
use App\Catalog\UI\Http\Form\Model\CategoryForm;

final class CreateCategoryMapper
{
    public function __invoke(CategoryForm $data): CreateCategory
    {
        return new CreateCategory(
            $data->name,
            $data->vatRateId,
            $data->defaultMarkup,
            $data->priceModel,
            $data->ownerId,
            $data->isActive,
        );
    }
}
