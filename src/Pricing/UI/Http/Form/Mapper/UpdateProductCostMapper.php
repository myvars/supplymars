<?php

namespace App\Pricing\UI\Http\Form\Mapper;

use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Pricing\Application\Command\UpdateProductCost;
use App\Pricing\UI\Http\Form\Model\ProductCostForm;

final class UpdateProductCostMapper
{
    public function __invoke(ProductCostForm $data): UpdateProductCost
    {
        return new UpdateProductCost(
            ProductPublicId::fromString($data->id),
            $data->defaultMarkup,
            $data->priceModel,
            $data->isActive
        );
    }
}
