<?php

namespace App\Review\UI\Http\Form\Mapper;

use App\Review\Application\Command\ReviewFilter;
use App\Review\Application\Search\ReviewSearchCriteria;

final class ReviewFilterMapper
{
    public function __invoke(ReviewSearchCriteria $criteria): ReviewFilter
    {
        return new ReviewFilter(
            query: $criteria->getQuery(),
            sort: $criteria->getSort(),
            sortDirection: $criteria->getSortDirection(),
            page: $criteria->getPage(),
            limit: $criteria->getLimit(),
            reviewStatus: $criteria->reviewStatus,
            productId: $criteria->productId,
            customerId: $criteria->customerId,
            rating: $criteria->rating,
        );
    }
}
