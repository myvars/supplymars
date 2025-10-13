<?php

namespace App\Shared\Infrastructure\Persistence\Search;

use App\Shared\Application\Search\SearchCriteriaInterface;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta;

final readonly class Paginator
{
    public function createPagination(AdapterInterface $adapter, int $page, int $limit): Pagerfanta
    {
        return Pagerfanta::createForCurrentPageWithMaxPerPage($adapter, $page, $limit);
    }

    // helper to create search pagination directly from repository and search criteria
    function searchPagination(
        FindByCriteriaInterface $repository,
        SearchCriteriaInterface $criteria,
    ): Pagerfanta
    {
        return $this->createPagination(
            $repository->findByCriteria($criteria),
            $criteria->getPage(),
            $criteria->getLimit());
    }
}
