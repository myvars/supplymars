<?php

namespace App\Catalog\UI\Http\Form\Mapper;


use App\Catalog\Application\Command\Category\CategoryFilter;
use App\Catalog\Application\Search\CategorySearchCriteria;

final class CategoryFilterMapper
{
    public function __invoke(CategorySearchCriteria $criteria): CategoryFilter
    {
        return new CategoryFilter(
            $criteria->getQuery(),
            $criteria->getSort(),
            $criteria->getSortDirection(),
            $criteria->getPage(),
            $criteria->getLimit(),
            $criteria->priceModel,
            $criteria->managerId,
            $criteria->vatRateId,
        );
    }
}
