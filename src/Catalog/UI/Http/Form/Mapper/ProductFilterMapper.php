<?php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Product\ProductFilter;
use App\Catalog\Application\Search\ProductSearchCriteria;

final class ProductFilterMapper
{
    public function __invoke(ProductSearchCriteria $criteria): ProductFilter
    {
        return new ProductFilter(
            $criteria->getQuery(),
            $criteria->getSort(),
            $criteria->getSortDirection(),
            $criteria->getPage(),
            $criteria->getLimit(),
            $criteria->mfrPartNumber,
            $criteria->categoryId,
            $criteria->subcategoryId,
            $criteria->manufacturerId,
            $criteria->inStock,
        );
    }
}
