<?php

namespace App\Catalog\UI\Http\Form\Mapper;


use App\Catalog\Application\Command\Subcategory\SubcategoryFilter;
use App\Catalog\Application\Search\SubcategorySearchCriteria;

final class SubcategoryFilterMapper
{
    public function __invoke(SubcategorySearchCriteria $criteria): SubcategoryFilter
    {
        return new SubcategoryFilter(
            $criteria->getQuery(),
            $criteria->getSort(),
            $criteria->getSortDirection(),
            $criteria->getPage(),
            $criteria->getLimit(),
            $criteria->categoryId,
            $criteria->priceModel,
            $criteria->managerId,
        );
    }
}
