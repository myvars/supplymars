<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Search;

use App\Shared\Application\Search\SearchCriteriaInterface;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta;

final readonly class Paginator
{
    /**
     * @template T
     *
     * @param AdapterInterface<T> $adapter
     *
     * @return Pagerfanta<T>
     */
    public function createPagination(AdapterInterface $adapter, int $page, int $limit): Pagerfanta
    {
        return Pagerfanta::createForCurrentPageWithMaxPerPage($adapter, $page, $limit);
    }

    /**
     * Helper to create search pagination directly from repository and search criteria.
     *
     * @return Pagerfanta<mixed>
     */
    public function searchPagination(
        FindByCriteriaInterface $repository,
        SearchCriteriaInterface $criteria,
    ): Pagerfanta {
        return $this->createPagination(
            $repository->findByCriteria($criteria),
            $criteria->getPage(),
            $criteria->getLimit());
    }
}
