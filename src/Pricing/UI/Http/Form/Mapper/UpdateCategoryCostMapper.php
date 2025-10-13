<?php

namespace App\Pricing\UI\Http\Form\Mapper;

use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Pricing\Application\Command\UpdateCategoryCost;
use App\Pricing\UI\Http\Form\Model\CategoryCostForm;

final class UpdateCategoryCostMapper
{
    public function __invoke(CategoryCostForm $data): UpdateCategoryCost
    {
        return new UpdateCategoryCost(
            CategoryPublicId::fromString($data->id),
            $data->defaultMarkup,
            $data->priceModel,
            $data->isActive
        );
    }
}
