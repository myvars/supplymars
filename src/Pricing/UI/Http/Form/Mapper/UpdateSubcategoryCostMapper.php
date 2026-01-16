<?php

namespace App\Pricing\UI\Http\Form\Mapper;

use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Pricing\Application\Command\UpdateSubcategoryCost;
use App\Pricing\UI\Http\Form\Model\SubcategoryCostForm;

final class UpdateSubcategoryCostMapper
{
    public function __invoke(SubcategoryCostForm $data): UpdateSubcategoryCost
    {
        return new UpdateSubcategoryCost(
            SubcategoryPublicId::fromString($data->id),
            $data->defaultMarkup,
            $data->priceModel,
            $data->isActive
        );
    }
}
