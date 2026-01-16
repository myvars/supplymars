<?php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Category\UpdateCategory;
use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Catalog\UI\Http\Form\Model\CategoryForm;

final class UpdateCategoryMapper
{
    public function __invoke(CategoryForm $data): UpdateCategory
    {
        return new UpdateCategory(
            CategoryPublicId::fromString($data->id),
            $data->name,
            $data->vatRateId,
            $data->defaultMarkup,
            $data->priceModel,
            $data->ownerId,
            $data->isActive
        );
    }
}
